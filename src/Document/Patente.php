<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: "patentes")]
class Patente
{
    #[ODM\Id]
    private $id;

    #[ODM\Field(type: "string")]
    private $numeroPedido;

    #[ODM\Field(type: "string")]
    private $cpc;

    #[ODM\Field(type: "string")]
    private $campoTecnico;

    #[ODM\Field(type: "string")]
    private $descricao;

    #[ODM\Field(type: "string")]
    private $reivindicacoes;

    #[ODM\Field(type: "date")]
    private $dataExtraida;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getNumeroPedido(): ?string
    {
        return $this->numeroPedido;
    }

    public function setNumeroPedido(string $numeroPedido): self
    {
        $this->numeroPedido = $numeroPedido;
        return $this;
    }

    public function getCpc(): ?string
    {
        return $this->cpc;
    }

    public function setCpc(string $cpc): self
    {
        $this->cpc = $cpc;
        return $this;
    }

    public function getCampoTecnico(): ?string
    {
        return $this->campoTecnico;
    }

    public function setCampoTecnico(string $campoTecnico): self
    {
        $this->campoTecnico = $campoTecnico;
        return $this;
    }

    public function getDescricao(): ?string
    {
        return $this->descricao;
    }

    public function setDescricao(string $descricao): self
    {
        $this->descricao = $descricao;
        return $this;
    }

    public function getReivindicacoes(): ?string
    {
        return $this->reivindicacoes;
    }

    public function setReivindicacoes(string $reivindicacoes): self
    {
        $this->reivindicacoes = $reivindicacoes;
        return $this;
    }

    public function getDataExtraida(): ?\DateTimeInterface
    {
        return $this->dataExtraida;
    }

    public function setDataExtraida(\DateTimeInterface $dataExtraida): self
    {
        $this->dataExtraida = $dataExtraida;
        return $this;
    }
}
