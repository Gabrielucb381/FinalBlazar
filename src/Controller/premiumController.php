<?php

namespace App\Controller;

use App\Document\premium;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class premiumController
{
    #[Route('/api/premium', name: 'api_premium', methods: ['POST'])]
    public function create(Request $request, DocumentManager $dm): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || empty($data['documento']) || empty($data['tipoPlano'])) {
            return new JsonResponse(['success' => false, 'message' => 'Dados inválidos.'], 400);
        }

        $tipoPlano = strtolower($data['tipoPlano']);
        if (!in_array($tipoPlano, ['pf', 'pj'])) {
            return new JsonResponse(['success' => false, 'message' => 'Tipo de plano inválido. Use "pf" ou "pj".'], 400);
        }

        $existente = $dm->getRepository(premium::class)->findOneBy(['cpf' => $data['documento']]);
        if ($existente) {
            return new JsonResponse(['success' => false, 'message' => 'Documento já cadastrado.'], 409);
        }

        $premium = new premium();
        $premium->setNome($data['nome']);
        $premium->setCpf($data['documento']);
        $premium->setEmail($data['email']);
        $premium->setNascimento($data['nascimento'] ?? '');
        $premium->setSenha($data['senha'] ?? '');
        $premium->setCep($data['cep'] ?? '');
        $premium->setEndereco($data['endereco'] ?? '');
        $premium->setNumeroDaCasa($data['numeroDaCasa'] ?? '');
        $premium->setComplemento($data['complemento'] ?? '');
        $premium->setEstado($data['estado'] ?? '');
        $premium->setCidade($data['cidade'] ?? '');
        $premium->setPontoDeReferencia($data['pontoDeReferencia'] ?? '');
        $premium->setTipoPlano($tipoPlano);
        $premium->setPagamentoConfirmado(false);
        $premium->setValidadeAcesso(null);
        $premium->setPaymentIntentId(null);
        $premium->setPaymentStatus(null);
        $premium->setCardOwnerName(null);
        $premium->setCpfTitular(null);
        $premium->setCardLast4(null);  // Adicionando campo para os últimos 4 dígitos do cartão
        $premium->setCardExpiry(null); // Adicionando campo para validade do cartão
        $premium->setPaymentDate(null);

        $dm->persist($premium);
        $dm->flush();

        return new JsonResponse(['success' => true, 'id' => $premium->getId()], 201);
    }

    #[Route('/create-payment-intent', name: 'create_payment_intent', methods: ['POST'])]
    public function createPaymentIntent(Request $request): JsonResponse
    {
        $stripeKey = $_ENV['STRIPE_SECRET_KEY'] ?? null;

        if (!$stripeKey) {
            return new JsonResponse(['error' => 'Chave da API Stripe não configurada.'], 500);
        }

        Stripe::setApiKey($stripeKey);

        $data = json_decode($request->getContent(), true);
        $tipoPlano = strtolower($data['plano'] ?? 'pj');

        if (!in_array($tipoPlano, ['pf', 'pj'])) {
            return new JsonResponse(['error' => 'Tipo de plano inválido.'], 400);
        }

        $amount = match ($tipoPlano) {
            'pf' => 200,
            'pj' => 100,
            default => 100,
        };

        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => $amount,
                'currency' => 'brl',
                'description' => 'Plano Gold Anual - ' . strtoupper($tipoPlano),
            ]);

            return new JsonResponse(['clientSecret' => $paymentIntent->client_secret]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erro ao criar o PaymentIntent: ' . $e->getMessage()], 400);
        }
    }

    #[Route('/api/premium/{id}/confirmar-pagamento', name: 'confirmar_pagamento', methods: ['PATCH'])]
    public function confirmarPagamento(string $id, Request $request, DocumentManager $dm): JsonResponse
    {
        $usuario = $dm->getRepository(premium::class)->find($id);

        if (!$usuario) {
            return new JsonResponse(['success' => false, 'message' => 'Usuário não encontrado com o ID fornecido.'], 404);
        }

        $data = json_decode($request->getContent(), true);

        $usuario->setPagamentoConfirmado(true);
        $usuario->setValidadeAcesso(new \DateTime('+1 year'));
        $usuario->setPaymentIntentId($data['paymentIntentId'] ?? null);
        $usuario->setPaymentStatus($data['paymentStatus'] ?? null);
        $usuario->setCardOwnerName($data['cardOwnerName'] ?? null);
        $usuario->setCpfTitular($data['cpfTitular'] ?? null);

        // Armazenando os dados de pagamento atualizados
        $usuario->setCardLast4($data['cardLast4'] ?? null);   // Armazenando os últimos 4 dígitos do cartão
        $usuario->setCardExpiry($data['cardExpiry'] ?? null);  // Armazenando validade do cartão

        try {
            $usuario->setPaymentDate(new \DateTime($data['paymentDate'] ?? 'now'));
        } catch (\Exception $e) {
            $usuario->setPaymentDate(new \DateTime());
        }

        $dm->flush();

        return new JsonResponse(['success' => true, 'message' => 'Pagamento confirmado e dados salvos com sucesso.'], 200);
    }

    #[Route('/api/premium/verifica-pagamento', name: 'verifica_pagamento', methods: ['POST'])]
    public function verificarPagamento(Request $request, DocumentManager $dm): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        if (!$email) {
            return new JsonResponse(['success' => false, 'message' => 'E-mail não fornecido.'], 400);
        }

        $usuario = $dm->getRepository(premium::class)->findOneBy(['email' => $email]);

        if (!$usuario) {
            return new JsonResponse(['success' => false, 'message' => 'Usuário não encontrado.'], 404);
        }

        return new JsonResponse([
            'success' => true,
            'pagamentoConfirmado' => $usuario->isPagamentoConfirmado(),
        ]);
    }

    #[Route('/api/premium/email/{email}', name: 'buscar_por_email', methods: ['GET'])]
    public function buscarPorEmail(string $email, DocumentManager $dm): JsonResponse
    {
        $decodedEmail = urldecode($email);
        $usuario = $dm->getRepository(premium::class)->findOneBy(['email' => $decodedEmail]);

        if (!$usuario) {
            return new JsonResponse(['success' => false, 'message' => 'Usuário não encontrado.'], 404);
        }

        return new JsonResponse([
            'success' => true,
            'id' => $usuario->getId(), 
            'pagamentoConfirmado' => $usuario->isPagamentoConfirmado(),
            'nome' => $usuario->getNome(),
            'cpf' => $usuario->getCpf(),
            'nascimento' => $usuario->getNascimento(),
            'email' => $usuario->getEmail(),
            'cep' => $usuario->getCep(),
            'endereco' => $usuario->getEndereco(),
            'numeroDaCasa' => $usuario->getNumeroDaCasa(),
            'complemento' => $usuario->getComplemento(),
            'estado' => $usuario->getEstado(),
            'cidade' => $usuario->getCidade(),
            'pontoDeReferencia' => $usuario->getPontoDeReferencia(),
            'cardOwnerName' => $usuario->getCardOwnerName(),
            'cpfTitular' => $usuario->getCpfTitular(),
            'paymentDate' => $usuario->getPaymentDate()?->format('Y-m-d') ?? '',
            'validadeAcesso' => $usuario->getValidadeAcesso()?->format('Y-m-d') ?? '',
            'tipoPlano' => $usuario->getTipoPlano(),
            'cardLast4' => $usuario->getCardLast4(),  // Exibindo os últimos 4 dígitos do cartão
            'cardExpiry' => $usuario->getCardExpiry(), // Exibindo validade do cartão
        ]);
    }

    #[Route('/api/premium/email/{email}', name: 'atualizar_por_email', methods: ['PATCH'])]
    public function atualizarPorEmail(string $email, Request $request, DocumentManager $dm): JsonResponse
    {
        $decodedEmail = urldecode($email);
        $usuario = $dm->getRepository(premium::class)->findOneBy(['email' => $decodedEmail]);

        if (!$usuario) {
            return new JsonResponse(['success' => false, 'message' => 'Usuário não encontrado.'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['nome'])) $usuario->setNome($data['nome']);
        if (isset($data['documento'])) $usuario->setCpf($data['documento']);
        if (isset($data['nascimento'])) $usuario->setNascimento($data['nascimento']);
        if (isset($data['cep'])) $usuario->setCep($data['cep']);
        if (isset($data['endereco'])) $usuario->setEndereco($data['endereco']);
        if (isset($data['numeroDaCasa'])) $usuario->setNumeroDaCasa($data['numeroDaCasa']);
        if (isset($data['complemento'])) $usuario->setComplemento($data['complemento']);
        if (isset($data['estado'])) $usuario->setEstado($data['estado']);
        if (isset($data['cidade'])) $usuario->setCidade($data['cidade']);
        if (isset($data['pontoDeReferencia'])) $usuario->setPontoDeReferencia($data['pontoDeReferencia']);

        if (isset($data['tipoPlano'])) {
            $tipoPlano = strtolower($data['tipoPlano']);
            if (!in_array($tipoPlano, ['pf', 'pj'])) {
                return new JsonResponse(['success' => false, 'message' => 'Tipo de plano inválido na atualização. Use "pf" ou "pj".'], 400);
            }
            $usuario->setTipoPlano($tipoPlano);
        }

        if (array_key_exists('senha', $data)) {
            $usuario->setSenha($data['senha'] ?? $usuario->getSenha());
        }

        // Atualizando dados de pagamento, como últimos 4 dígitos e validade
        if (isset($data['cardLast4'])) $usuario->setCardLast4($data['cardLast4']);
        if (isset($data['cardExpiry'])) $usuario->setCardExpiry($data['cardExpiry']);

        $dm->flush();

        return new JsonResponse(['success' => true, 'message' => 'Dados atualizados com sucesso.']);
    }

    #[Route('/api/premium/{id}/alterar-plano', name: 'alterar_plano', methods: ['PATCH'])]
    public function alterarPlano(string $id, Request $request, DocumentManager $dm): JsonResponse
    {
        $usuario = $dm->getRepository(premium::class)->find($id);
        if (!$usuario) {
            return new JsonResponse(['success' => false, 'message' => 'Usuário não encontrado.'], 404);
        }
    
        $data = json_decode($request->getContent(), true);
        $novoPlano = strtolower($data['tipoPlano'] ?? 'pj');
        if (!in_array($novoPlano, ['pf', 'pj'])) {
            return new JsonResponse(['success' => false, 'message' => 'Tipo de plano inválido.'], 400);
        }
    
        // Atualiza o tipo de plano no banco de dados
        $usuario->setTipoPlano($novoPlano);
        $dm->flush();
    
        // Atualiza a assinatura na Stripe
        try {
            Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
    
            // Usando o ID da assinatura (não o PaymentIntent ID)
            $subscriptionId = $usuario->getPaymentIntentId();  // Verifique se o PaymentIntentId é realmente o ID da assinatura
            $subscription = \Stripe\Subscription::retrieve($subscriptionId);
    
            // Verifica o novo plano
            $novoPriceId = ($novoPlano === 'pf') ? $_ENV['STRIPE_PRICE_ID_PF'] : $_ENV['STRIPE_PRICE_ID_PJ'];
    
            // Atualiza o plano na assinatura da Stripe
            $subscription->update([
                'items' => [
                    [
                        'id' => $subscription->items->data[0]->id,
                        'price' => $novoPriceId,
                    ]
                ]
            ]);
            
    
            return new JsonResponse(['success' => true, 'message' => 'Plano alterado com sucesso.']);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erro ao alterar plano na Stripe: ' . $e->getMessage()], 500);
        }
    }    
}
