<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

#[MongoDB\Document]
class premium
{
    #[MongoDB\Id]
    private $id;

    #[MongoDB\Field(type: "string")]
    private $nome;

    #[MongoDB\Field(type: "string")]
    private $cpf;

    #[MongoDB\Field(type: "string")]
    private $email;

    #[MongoDB\Field(type: "string")]
    private $senha;

    #[MongoDB\Field(type: "string")]
    private $nascimento;

    #[MongoDB\Field(type: "string")]
    private $cep;

    #[MongoDB\Field(type: "string")]
    private $endereco;

    #[MongoDB\Field(type: "string")]
    private $numeroDaCasa;

    #[MongoDB\Field(type: "string")]
    private $complemento;

    #[MongoDB\Field(type: "string")]
    private $estado;

    #[MongoDB\Field(type: "string")]
    private $cidade;

    #[MongoDB\Field(type: "string")]
    private $pontoDeReferencia;

    #[MongoDB\Field(type: "bool")]
    private $pagamentoConfirmado = false;

    #[MongoDB\Field(type: "date", nullable: true)]
    private $validadeAcesso;

    #[MongoDB\Field(type: "string", nullable: true)]
    private $paymentIntentId;

    #[MongoDB\Field(type: "string", nullable: true)]
    private $paymentStatus;

    #[MongoDB\Field(type: "string", nullable: true)]
    private $cardOwnerName;

    #[MongoDB\Field(type: "string", nullable: true)]
    private $cpfTitular;

    #[MongoDB\Field(type: "date", nullable: true)]
    private $paymentDate;

    #[MongoDB\Field(type: "string", nullable: true)]
    private $tipoPlano;

    #[MongoDB\Field(type: "string", nullable: true)]
    private $cardLast4;

    #[MongoDB\Field(type: "string", nullable: true)]
    private $cardExpiry;

    // Getters e Setters
    public function getId(): ?string { return $this->id; }

    public function getNome(): ?string { return $this->nome; }
    public function setNome(string $nome): self { $this->nome = $nome; return $this; }

    public function getCpf(): ?string { return $this->cpf; }
    public function setCpf(string $cpf): self { $this->cpf = $cpf; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }

    public function getNascimento(): ?string { return $this->nascimento; }
    public function setNascimento(string $nascimento): self { $this->nascimento = $nascimento; return $this; }

    public function getSenha(): ?string { return $this->senha; }
    public function setSenha(string $senha): self { $this->senha = $senha; return $this; }

    public function getCep(): ?string { return $this->cep; }
    public function setCep(string $cep): self { $this->cep = $cep; return $this; }

    public function getEndereco(): ?string { return $this->endereco; }
    public function setEndereco(string $endereco): self { $this->endereco = $endereco; return $this; }

    public function getNumeroDaCasa(): ?string { return $this->numeroDaCasa; }
    public function setNumeroDaCasa(string $numero): self { $this->numeroDaCasa = $numero; return $this; }

    public function getComplemento(): ?string { return $this->complemento; }
    public function setComplemento(string $complemento): self { $this->complemento = $complemento; return $this; }

    public function getEstado(): ?string { return $this->estado; }
    public function setEstado(string $estado): self { $this->estado = $estado; return $this; }

    public function getCidade(): ?string { return $this->cidade; }
    public function setCidade(string $cidade): self { $this->cidade = $cidade; return $this; }

    public function getPontoDeReferencia(): ?string { return $this->pontoDeReferencia; }
    public function setPontoDeReferencia(string $ponto): self { $this->pontoDeReferencia = $ponto; return $this; }

    public function isPagamentoConfirmado(): bool { return $this->pagamentoConfirmado; }
    public function setPagamentoConfirmado(bool $confirmado): self { $this->pagamentoConfirmado = $confirmado; return $this; }

    public function getValidadeAcesso(): ?\DateTimeInterface { return $this->validadeAcesso; }
    public function setValidadeAcesso(?\DateTimeInterface $data): self { $this->validadeAcesso = $data; return $this; }

    public function getPaymentIntentId(): ?string { return $this->paymentIntentId; }
    public function setPaymentIntentId(?string $paymentIntentId): self { $this->paymentIntentId = $paymentIntentId; return $this; }

    public function getPaymentStatus(): ?string { return $this->paymentStatus; }
    public function setPaymentStatus(?string $paymentStatus): self { $this->paymentStatus = $paymentStatus; return $this; }

    public function getCardOwnerName(): ?string { return $this->cardOwnerName; }
    public function setCardOwnerName(?string $cardOwnerName): self { $this->cardOwnerName = $cardOwnerName; return $this; }

    public function getCpfTitular(): ?string { return $this->cpfTitular; }
    public function setCpfTitular(?string $cpfTitular): self { $this->cpfTitular = $cpfTitular; return $this; }

    public function getPaymentDate(): ?\DateTimeInterface { return $this->paymentDate; }
    public function setPaymentDate(?\DateTimeInterface $paymentDate): self { $this->paymentDate = $paymentDate; return $this; }

    public function getTipoPlano(): ?string { return $this->tipoPlano; }
    public function setTipoPlano(?string $tipoPlano): self { $this->tipoPlano = $tipoPlano; return $this; }

    public function getCardLast4(): ?string { return $this->cardLast4; }
    public function setCardLast4(?string $last4): self { $this->cardLast4 = $last4; return $this; }

    public function getCardExpiry(): ?string { return $this->cardExpiry; }
    public function setCardExpiry(?string $expiry): self { $this->cardExpiry = $expiry; return $this; }
}
