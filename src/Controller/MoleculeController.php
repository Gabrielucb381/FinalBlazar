<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class MoleculeController
{
    #[Route('/api/molecule', name: 'get_molecule', methods: ['GET'])]
    public function getMolecule(Request $request): JsonResponse
    {
        $nome = $request->query->get('q');
        if (!$nome) return new JsonResponse(['erro' => 'Nome não informado'], 400);

        $filename = preg_replace('/[^a-zA-Z0-9]/', '_', strtolower($nome)) . '.sdf';
        $filePath = __DIR__ . '/../../public/modelos/' . $filename;

        if (!file_exists($filePath)) {
            $sdf = @file_get_contents("https://pubchem.ncbi.nlm.nih.gov/rest/pug/compound/name/{$nome}/SDF");
            if (!$sdf) return new JsonResponse(['erro' => 'Molécula não encontrada'], 404);
            file_put_contents($filePath, $sdf);
        }

        return new JsonResponse(['arquivo_sdf' => "/modelos/$filename", 'nome' => $nome]);
    }
}
