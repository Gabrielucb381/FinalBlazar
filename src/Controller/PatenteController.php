<?php

namespace App\Controller;

use App\Document\Patente;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class PatenteController
{
    #[Route('/api/patentes', name: 'salvar_patente', methods: ['POST'])]
    public function salvar(Request $request, DocumentManager $dm): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || empty($data['numero_pedido'])) {
            return new JsonResponse(['success' => false, 'message' => 'Dados inválidos'], 400);
        }

        $patente = new Patente();
        $patente->setNumeroPedido($data['numero_pedido']);
        $patente->setCpc($data['cpc'] ?? '');
        $patente->setCampoTecnico($data['campo_tecnico'] ?? '');
        $patente->setDescricao($data['descricao'] ?? '');
        $patente->setReivindicacoes($data['reivindicacoes'] ?? '');
        $patente->setDataExtraida(new \DateTime());

        $dm->persist($patente);
        $dm->flush();

        return new JsonResponse(['success' => true, 'message' => 'Patente salva com sucesso']);
    }

    #[Route('/api/patentes', name: 'listar_patentes', methods: ['GET'])]
    public function listar(DocumentManager $dm): JsonResponse
    {
        $repo = $dm->getRepository(Patente::class);
        $patentes = $repo->findAll();

        $result = [];
        foreach ($patentes as $p) {
            $result[] = [
                'numero_pedido' => $p->getNumeroPedido(),
                'cpc' => $p->getCpc(),
                'campo_tecnico' => $p->getCampoTecnico(),
                'descricao' => $p->getDescricao(),
                'reivindicacoes' => $p->getReivindicacoes(),
                'data_extraida' => $p->getDataExtraida()?->format('Y-m-d H:i:s')
            ];
        }

        return new JsonResponse(['success' => true, 'data' => $result]);
    }
}
