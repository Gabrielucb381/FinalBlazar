<?php

require 'vendor/autoload.php';

use Symfony\Component\Panther\Client;
use Facebook\WebDriver\Cookie;
use Facebook\WebDriver\WebDriverBy;
use MongoDB\Client as MongoClient;
use MongoDB\BSON\UTCDateTime;
use Smalot\PdfParser\Parser;
use Symfony\Component\DomCrawler\Crawler;

// -----------------------------------------------
// 📌 CONFIGURAÇÕES
// -----------------------------------------------

const URL_HOME = "https://busca.inpi.gov.br/pePI/";

// Cookie de sessão válido
$cookieSessionId = "75A7EA847BE206B5ACF942291F02E981.plbusca02";

// CPCs alvo
$listaCpcs = [
    "A61K 31/00",
    "C12N 15/00",
];

// MongoDB
$mongo = new MongoClient('mongodb://localhost:27017');
$db = $mongo->doctrine;
$patentes = $db->patentes;

// Pasta local
if (!is_dir(__DIR__.'/patentes')) {
    mkdir(__DIR__.'/patentes');
}

// -----------------------------------------------
// 📌 Panther
// -----------------------------------------------

$client = Client::createChromeClient(
    '/usr/bin/google-chrome', // Caminho para o ChromeDriver
    [
        'chromeOptions' => [
            'args' => [
                '--no-sandbox',
                '--disable-gpu',
                '--headless=false'  // Se quiser ver o Chrome aberto
            ]
        ]
    ]
);





$client->request('GET', URL_HOME);

// Passo: clique em Continuar se aparecer
$client->waitFor('//a[contains(text(),"Continuar")]', 40);
$client->getWebDriver()->findElement(WebDriverBy::xpath('//a[contains(text(),"Continuar")]'))->click();
echo "[✓] Continuar clicado\n";

sleep(5);  // Espera 5 segundos para garantir que o ChromeDriver tenha tempo de iniciar
$client->request('GET', URL_HOME);

$client->waitFor('//a[contains(text(),"Patente")]', 40);
$client->getWebDriver()->findElement(WebDriverBy::xpath('//a[contains(text(),"Patente")]'))->click();
echo "[✓] Abriu Patente\n";

$client->waitFor('//a[contains(text(),"Pesquisa Avançada")]', 30);  
$client->getWebDriver()->findElement(WebDriverBy::xpath('//a[contains(text(),"Pesquisa Avançada")]'))->click();
echo "[✓] Abriu Pesquisa Avançada\n";

// -----------------------------------------------
// 📌 Loop CPC
// -----------------------------------------------

foreach ($listaCpcs as $cpc) {
    echo "\n>>> Buscando: $cpc\n";

    $client->waitFor('input[name="numClassificacao"]', 20);
    $input = $client->getWebDriver()->findElement(WebDriverBy::name('numClassificacao'));
    $input->clear();
    $input->sendKeys($cpc);

    $button = $client->getWebDriver()->findElement(WebDriverBy::name('botaoPesquisar'));
    $button->click();

    sleep(2);

    do {
        $client->waitFor('table.table-pesquisa', 20);

        $rows = $client->getCrawler()->filter('table.table-pesquisa tbody tr');

        foreach ($rows as $tr) {
            $rowCrawler = new Crawler($tr);
            $linkNode = $rowCrawler->filter('a');

            if ($linkNode->count()) {
                $href = $linkNode->attr('href');
                $numero = trim($rowCrawler->filter('td')->eq(0)->text());

                $pdfUrl = "https://busca.inpi.gov.br/pePI/{$href}";
                $localPdf = __DIR__ . "/patentes/{$numero}.pdf";
                file_put_contents($localPdf, file_get_contents($pdfUrl));
                echo "  [↓] PDF: $numero\n";

                $parser = new Parser();
                $pdf = $parser->parseFile($localPdf);
                $texto = $pdf->getText();

                $campo_tecnico = strstr($texto, "Campo Técnico") ?: "";
                $descricao = strstr($texto, "Descrição") ?: "";
                $reivindicacoes = strstr($texto, "Reivindicações") ?: "";

                $json = [
                    'numero_pedido' => $numero,
                    'cpc' => $cpc,
                    'campo_tecnico' => $campo_tecnico,
                    'descricao' => $descricao,
                    'reivindicacoes' => $reivindicacoes,
                    'pdf_local' => $localPdf,
                    'pdf_url' => $pdfUrl,
                    'data_extraida' => new UTCDateTime(), // 💡 CORRETO COM use
                ];

                $patentes->insertOne($json);
                echo "  [✓] MongoDB salvo: $numero\n";
            }
        }

        $next = $client->getCrawler()->selectLink('próxima');
        if (count($next) === 0) break;

        $client->click($next->link());
        sleep(rand(1, 2));

    } while (true);
}

$client->quit();
echo "\n[🏁] Finalizado!\n";
