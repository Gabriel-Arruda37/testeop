  <?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configurações de API
define('BINANCE_API', 'https://api.binance.com/api/v3/ticker/price');
define('COINGECKO_API_URL', 'https://api.coingecko.com/api/v3/coins/');
define('INVESTIMENTO_INICIAL', 1000); 
define('DIFERENCA_MINIMA', 0.01); 
define('TELEGRAM_BOT_TOKEN', 'seu_token_telegram'); 
define('TELEGRAM_CHAT_ID', 'seu_chat_id_telegram'); 

// Função para obter o preço de uma moeda na Binance
function get_binance_prices() {
    $response = file_get_contents(BINANCE_API);
    return json_decode($response, true);
}

// Função para obter os preços de outras exchanges no CoinGecko
function get_coingecko_prices($coin_id) {
    $url = COINGECKO_API_URL . $coin_id . '/tickers';
    $response = file_get_contents($url);
    return json_decode($response, true);
}

// Função para calcular o lucro
function calcular_lucro($preco_binance, $preco_outro) {
    return ($preco_outro - $preco_binance) / $preco_binance * INVESTIMENTO_INICIAL;
}

// Função para detectar oportunidades de arbitragem
function detectar_oportunidades($binance_data) {
    $oportunidades = [];
    foreach ($binance_data as $moeda) {
        $symbol = $moeda['symbol'];
        $preco_binance = $moeda['price'];
        $coin_id = strtolower(str_replace('USDT', '', $symbol)); 

        $coingecko_data = get_coingecko_prices($coin_id);
        if (isset($coingecko_data['tickers'])) {
            foreach ($coingecko_data['tickers'] as $ticker) {
                $preco_outro = $ticker['last'];
                $exchange = $ticker['market']['name'];

                $diferenca = $preco_outro - $preco_binance;
                if ($diferenca >= DIFERENCA_MINIMA) {
                    $lucro = calcular_lucro($preco_binance, $preco_outro);
                    $oportunidades[] = [
                        'moeda' => $symbol,
                        'preco_binance' => $preco_binance,
                        'preco_outro' => $preco_outro,
                        'exchange' => $exchange,
                        'diferenca' => $diferenca,
                        'lucro' => $lucro
                    ];
                }
            }
        }
    }
    return $oportunidades;
}

// Função para enviar oportunidades para o Telegram
function enviar_para_telegram($oportunidades) {
    if (empty($oportunidades)) return;

    $mensagem = "📈 Oportunidades de Arbitragem Detectadas 📉\n\n";
    foreach ($oportunidades as $op) {
        $mensagem .= "Moeda: {$op['moeda']}\n";
        $mensagem .= "Preço Binance: R$ {$op['preco_binance']}\n";
        $mensagem .= "Preço Outra: R$ {$op['preco_outro']}\n";
        $mensagem .= "Exchange: {$op['exchange']}\n";
        $mensagem .= "Diferença: R$ {$op['diferenca']}\n";
        $mensagem .= "Lucro Estimado: R$ {$op['lucro']}\n\n";
    }

    $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";
    $data = [
        'chat_id' => TELEGRAM_CHAT_ID,
        'text' => $mensagem
    ];

    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
        ],
    ];

    $context  = stream_context_create($options);
    file_get_contents($url, false, $context);
}

// Loop principal
while (true) {
    $binance_data = get_binance_prices();
    $oportunidades = detectar_oportunidades($binance_data);
    enviar_para_telegram($oportunidades);
    sleep(60);
}

?>
