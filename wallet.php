<!-- 
    DYNAMIC USERS WALLET SESSION 
    
    this will be included dynamically as the users  wallet for any page that needs to have the users wallet
-->
<style>
        :root {
            --background: #0f172a;
            --surface: #1e293b;
            --text-color: #ffffff;
            --secondary-text: #8b8ca7;
            --primary-dark: #4f46e5;
            --primary-color: #6366f1;
            --border-color: #2a2f3e;
            --hover-color: rgba(255, 255, 255, 0.05);
            --positive-color: #00c853;
            --negative-color: #ff0000;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;

        }

        body {
            background-color: var(--background);
            color: var(--text-color);
        }
        /* .crypto-container {
            max-width: 1200px; 
             margin: 0 auto;
        } */

        .crypto-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .crypto-left {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
        }

        .crypto-icon {
            width: 35px;
            height: 35px;
            border-radius: 50%;
        }

        .crypto-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .crypto-name {
            font-size: 16px;
            font-weight: 500;
            color: var(--text-color);
        }

        .crypto-network {
            font-size: 14px;
            color: var(--secondary-text);
        }

        .crypto-right {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 4px;
        }

        .crypto-amount {
            font-size: 16px;
            color: var(--text-color);
        }

        .crypto-price {
            font-size: 14px;
            color: var(--secondary-text);
        }

        .crypto-change {
            font-size: 14px;
        }

        .crypto-change.positive {
            color: var(--positive-color);
        }

        .crypto-change.negative {
            color: var(--negative-color);
        }

        @media (max-width: 768px) {
            .crypto-row {
                padding: 15px;
            }

            .crypto-left {
                gap: 10px;
            }

            .crypto-icon {
                width: 28px;
                height: 28px;
            }

            .crypto-name {
                font-size: 14px;
            }

            .crypto-network {
                font-size: 12px;
            }

            .crypto-amount {
                font-size: 14px;
            }

            .crypto-price {
                font-size: 12px;
            }
        }
</style>
<?php

        include 'wallet_logic.php';

?>
<!-- <div class="crypto-container" id="cryptoContainer"></div>
<div id="offlineBanner" style="display: none; background: #ffcc00; color: #000; padding: 10px; text-align: center;">
    You are offline. Showing last updated data.
</div>
<div id="cryptoContainer"></div> -->

<div id="offlineBanner" style="display:none; color:red;">You are offline. Showing cached data.</div>
<div id="cryptoContainer"></div>

<script>
    // Pass the processed PHP data into JavaScript
    const cryptoDataFromServer = <?php echo json_encode($cryptoData); ?>;

    // Cache for the last successfully fetched data and prices
    let lastPrices = JSON.parse(localStorage.getItem('lastPrices')) || {}; // Load from LocalStorage
    let lastFetchedData = {}; // Cached fetched data

    // Static data for cryptocurrencies
    const staticCryptoData = [
        { id: 'bitcoin', symbol: 'BTC', network: 'BTC', icon: 'https://assets.coingecko.com/coins/images/1/small/bitcoin.png', amount: 0 },
        { id: 'tether', symbol: 'USDT', network: 'TRC20', icon: 'https://assets.coingecko.com/coins/images/325/small/Tether.png', amount: 0 },
        { id: 'ethereum', symbol: 'ETH', network: 'ERC20', icon: 'https://assets.coingecko.com/coins/images/279/small/ethereum.png', amount: 0 },
        { id: 'dogecoin', symbol: 'DOGE', network: 'DOGE', icon: 'https://assets.coingecko.com/coins/images/5/small/dogecoin.png', amount: 0 },
        { id: 'binancecoin', symbol: 'BNB', network: 'BNB-BSC', icon: 'https://assets.coingecko.com/coins/images/825/small/bnb-icon2_2x.png', amount: 0 },
        { id: 'shiba-inu', symbol: 'SHIB', network: 'ERC20', icon: 'https://assets.coingecko.com/coins/images/11939/small/shiba.png', amount: 0 },
        { id: 'litecoin', symbol: 'LTC', network: 'LTC', icon: 'https://assets.coingecko.com/coins/images/2/small/litecoin.png', amount: 0 },
        { id: 'ripple', symbol: 'XRP', network: 'XRP', icon: 'https://assets.coingecko.com/coins/images/44/small/xrp-symbol-white-128.png', amount: 0 },
    ];

    // Merge server-side data with static data
    staticCryptoData.forEach(crypto => {
        const serverAmount = cryptoDataFromServer[crypto.symbol] || 0;
        crypto.amount = serverAmount; // Update amount from server data
    });

    // Display crypto data
    function displayCryptoData(data) {
        const container = document.getElementById('cryptoContainer');
        container.innerHTML = ''; // Clear the container for fresh data

        staticCryptoData.forEach(crypto => {
            const priceData = data[crypto.id] || {};
            const price = Number(priceData.usd || 0); // Current price from API
            const previousPrice = Number(lastPrices[crypto.id] || price); // Use cached price or current if no cache exists

            // Calculate percentage change (use previousPrice before updating lastPrices)
            const change24h = previousPrice > 0 ? ((price - previousPrice) / previousPrice) * 100 : 0;

            // Update the cache AFTER the calculation
            lastPrices[crypto.id] = price;

            const cryptoAmount = price > 0 ? (crypto.amount / price).toFixed(4) : '0.0000';

            const row = document.createElement('div');
            row.className = 'crypto-row';
            row.innerHTML = `
                <div class="crypto-left">
                    <img src="${crypto.icon}" alt="${crypto.symbol}" class="crypto-icon">
                    <div class="crypto-info">
                        <div class="crypto-name">${crypto.symbol}</div>
                        <div class="crypto-network">${crypto.network}</div>
                    </div>
                </div>
                <div class="crypto-right">
                    <div class="crypto-amount">${cryptoAmount}</div>
                    <div class="crypto-price">$${price.toFixed(2)}</div>
                    <div class="crypto-change ${change24h >= 0 ? 'positive' : 'negative'}">
                        ${change24h > 0 ? '+' : ''}${change24h.toFixed(2)}%
                    </div>
                </div>
            `;
            container.appendChild(row);
        });

        // Save the updated lastPrices to LocalStorage
        localStorage.setItem('lastPrices', JSON.stringify(lastPrices));
    }

    // Fetch crypto prices
    async function fetchCryptoPrices() {
        if (!navigator.onLine) {
            console.warn('No internet connection. Using cached data.');
            showOfflineMessage();
            displayCryptoData(lastFetchedData); // Show cached data
            return;
        }

        try {
            const ids = staticCryptoData.map(crypto => crypto.id).join(',');
            const response = await fetchWithTimeout(`https://api.coingecko.com/api/v3/simple/price?ids=${ids}&vs_currencies=usd`, {}, 10000);
            const data = await response.json();

            // Update lastFetchedData cache
            lastFetchedData = data;

            // Display updated data
            displayCryptoData(data);
            hideOfflineMessage();
        } catch (error) {
            console.error('Error fetching crypto prices:', error);
            console.warn('Using cached data.');
            displayCryptoData(lastFetchedData); // Show cached data if an error occurs
        }
    }

    // Fetch with a timeout
    async function fetchWithTimeout(url, options = {}, timeout = 10000) {
        const controller = new AbortController();
        const id = setTimeout(() => controller.abort(), timeout);
        try {
            const response = await fetch(url, { ...options, signal: controller.signal });
            clearTimeout(id);
            return response;
        } catch (error) {
            clearTimeout(id);
            throw error;
        }
    }

    // Show offline message
    function showOfflineMessage() {
        const banner = document.getElementById('offlineBanner');
        banner.style.display = 'block';
    }

    // Hide offline message
    function hideOfflineMessage() {
        const banner = document.getElementById('offlineBanner');
        banner.style.display = 'none';
    }

    // Ensure localStorage has initial data before using it
    if (Object.keys(lastPrices).length === 0) {
        // Set the first fetch as initial data if lastPrices is empty
        fetchCryptoPrices();
    }

    // Initial fetch and set interval for updates
    fetchCryptoPrices();
    setInterval(fetchCryptoPrices, 60000); // Fetch every 1 minute to get live updates
</script>



</body>
</html>