<?php
define('GAME_NAME', 'baccarat');
require_once __DIR__ . '/../hack/_template.php';
$gameParam = isset($_GET['game']) ? $_GET['game'] : 'baccarat_ae';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cyber Baccarat Ultimate Lobby</title>
    <style>
        /* --- CYBERPUNK STYLING & BASE --- */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }

        body {
            background-color: #060913;
            color: #ffffff;
            padding: 25px;
            overflow-x: hidden;
        }

        /* --- DASHBOARD HEADER --- */
        .dashboard-header {
            background: rgba(16, 22, 37, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid #1f293d;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
        }

        .brand-title {
            font-size: 1.6rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 2px;
            background: linear-gradient(90deg, #00f3ff, #ff0055);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 0 20px rgba(0, 243, 255, 0.2);
        }

        /* Thanh điều khiển: Tìm kiếm + Bộ lọc */
        .controls-wrapper {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .search-box {
            background: #0d1527;
            border: 1px solid #243757;
            padding: 10px 15px;
            border-radius: 8px;
            color: #fff;
            font-size: 0.9rem;
            outline: none;
            width: 200px;
            transition: all 0.3s;
        }

        .search-box:focus {
            border-color: #00f3ff;
            box-shadow: 0 0 10px rgba(0, 243, 255, 0.3);
        }

        .filter-btn {
            background: #11192e;
            border: 1px solid #243757;
            color: #8fa0c2;
            padding: 10px 20px;
            font-size: 0.85rem;
            font-weight: bold;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .filter-btn:hover, .filter-btn.active {
            background: #00f3ff;
            color: #000;
            border-color: #00f3ff;
            box-shadow: 0 0 15px rgba(0, 243, 255, 0.4);
        }

        .filter-btn.btn-vip-filter:hover, .filter-btn.btn-vip-filter.active {
            background: #ffaa00;
            color: #000;
            border-color: #ffaa00;
            box-shadow: 0 0 15px rgba(255, 170, 0, 0.4);
        }

        /* --- LOBBY GRID MÁY CHỦ --- */
        .lobby-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(490px, 1fr));
            gap: 25px;
            max-width: 1700px;
            margin: 0 auto;
        }

        /* --- KHUNG BÀN CHƠI (TABLE CARD) --- */
        .table-card {
            background: rgba(17, 24, 39, 0.7);
            backdrop-filter: blur(8px);
            border: 1px solid #24324d;
            border-radius: 14px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
            position: relative;
        }

        /* Hiệu ứng Hover chung */
        .table-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
        }

        /* Phân loại Bàn Thường (Glow Xanh) */
        .table-card.standard-card:hover {
            border-color: #00f3ff;
            box-shadow: 0 0 20px rgba(0, 243, 255, 0.25);
        }

        /* Phân loại Bàn VIP (Glow Vàng Gold Hoàng Gia) */
        .table-card.vip-card {
            background: linear-gradient(145deg, #181528, #0e0c16);
            border-color: #44321a;
        }
        .table-card.vip-card:hover {
            border-color: #ffaa00;
            box-shadow: 0 0 20px rgba(255, 170, 0, 0.35);
        }

        /* HEADER CỦA BÀN */
        .table-header {
            background: rgba(26, 36, 56, 0.5);
            padding: 12px 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .table-title {
            font-size: 1.1rem;
            font-weight: 800;
            letter-spacing: 0.5px;
        }
        .standard-card .table-title { color: #00f3ff; }
        .vip-card .table-title { color: #ffaa00; text-shadow: 0 0 10px rgba(255,170,0,0.2); }

        /* TAG HIỂN THỊ TRẠNG THÁI BÀN */
        .table-tag {
            font-size: 0.7rem;
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .standard-card .table-tag { background: rgba(0, 243, 255, 0.15); color: #00f3ff; }
        .vip-card .table-tag { background: rgba(255, 170, 0, 0.15); color: #ffaa00; }

        /* --- THÂN BÀN CHƠI CHIA LÀM 3 KHỐI ĐỀU ĐẶN --- */
        .table-body {
            display: flex;
            height: 150px;
            background-color: #050811;
            position: relative;
        }

        /* KHỐI 1: Ô chứa Ảnh Dealer/Baccarat lớn */
        .table-avatar-container {
            width: 125px;
            height: 100%;
            border-right: 1px solid rgba(255,255,255,0.05);
            overflow: hidden;
            background: #090e1a;
        }

        .table-avatar-large {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.5s;
        }
        .table-card:hover .table-avatar-large {
            transform: scale(1.08);
        }

        /* KHỐI 2: Đồ thị / Hệ thống Lưới Cầu Baccarat chuẩn Casino */
        .chart-area {
            flex: 1;
            border-right: 1px solid rgba(255,255,255,0.05);
            padding: 6px;
            display: grid;
            grid-template-columns: repeat(11, 1fr);
            grid-template-rows: repeat(5, 1fr);
            gap: 4px;
            background: #03050a;
        }

        /* Các hạt chấm cầu tròn xịn sò */
        .bcr-bead {
            border-radius: 50%;
            width: 100%;
            height: 100%;
            max-width: 14px;
            max-height: 14px;
            margin: auto;
        }
        .bcr-bead.banker { background: #ff3838; box-shadow: 0 0 6px #ff3838; } /* Nhà Cái */
        .bcr-bead.player { background: #0084ff; box-shadow: 0 0 6px #0084ff; } /* Nhà Con */
        .bcr-bead.tie { background: #2ed573; box-shadow: 0 0 6px #2ed573; }    /* Hòa */
        .bcr-bead.empty { background: rgba(255,255,255,0.03); }

        /* CHỮ BÁO LỆNH NEON CHỚP TẮT */
        .status-alert {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(255, 0, 85, 0.95);
            color: #fff;
            font-weight: 900;
            font-size: 0.95rem;
            padding: 6px 16px;
            border-radius: 6px;
            letter-spacing: 1px;
            box-shadow: 0 0 15px #ff0055;
            animation: cyber-blink 1s infinite alternate;
            z-index: 5;
            pointer-events: none;
        }

        @keyframes cyber-blink {
            0% { opacity: 1; transform: translate(-50%, -50%) scale(1); }
            100% { opacity: 0.4; transform: translate(-50%, -50%) scale(0.96); }
        }

        /* KHỐI 3: Ô hiển thị Tỷ lệ thắng bên phải */
        .rate-area {
            width: 125px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 10px;
            background: linear-gradient(to right, #050811, #090e1a);
        }

        .rate-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            color: #697c9c;
            letter-spacing: 1px;
            margin-bottom: 6px;
        }

        .rate-value {
            font-size: 1.5rem;
            font-weight: 900;
            color: #ff0055;
            text-shadow: 0 0 10px rgba(255, 0, 85, 0.5);
            transition: color 0.5s ease;
        }
        /* Đổi màu tỷ lệ cực cao sang màu lục bảo */
        .rate-high {
            color: #00ff66 !important;
            text-shadow: 0 0 10px rgba(0, 255, 102, 0.5) !important;
        }

        /* FOOTER CỦA BÀN CHƠI */
        .table-footer {
            background: rgba(19, 26, 43, 0.4);
            padding: 12px 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid rgba(255,255,255,0.03);
        }

        /* Các đốm sáng chỉ báo tín hiệu mạng/trạng thái */
        .live-pulse {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.75rem;
            color: #697c9c;
        }
        .pulse-dot {
            width: 6px;
            height: 6px;
            background-color: #00ff66;
            border-radius: 50%;
            box-shadow: 0 0 8px #00ff66;
            animation: pulse-eff 1.5s infinite;
        }
        @keyframes pulse-eff {
            0% { transform: scale(0.8); opacity: 0.5; }
            50% { transform: scale(1.3); opacity: 1; }
            100% { transform: scale(0.8); opacity: 0.5; }
        }

        /* NÚT BẮT ĐẦU VIP */
        .btn-start {
            background: linear-gradient(90deg, #11192e, #1a2640);
            border: 1px solid #243757;
            color: #fff;
            padding: 8px 22px;
            font-size: 0.8rem;
            font-weight: 800;
            border-radius: 6px;
            cursor: pointer;
            text-transform: uppercase;
            transition: all 0.3s;
        }
        .standard-card .btn-start:hover {
        .table-card.vip-card {
            background: linear-gradient(145deg, #181528, #0e0c16);
            border-color: #44321a;
        }
        .table-card.vip-card:hover {
            border-color: #ffaa00;
            box-shadow: 0 0 20px rgba(255, 170, 0, 0.35);
        }

        /* HEADER CỦA BÀN */
        .table-header {
            background: rgba(26, 36, 56, 0.5);
            padding: 12px 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .table-title {
            font-size: 1.1rem;
            font-weight: 800;
            letter-spacing: 0.5px;
        }
        .standard-card .table-title { color: #00f3ff; }
        .vip-card .table-title { color: #ffaa00; text-shadow: 0 0 10px rgba(255,170,0,0.2); }

        /* TAG HIỂN THỊ TRẠNG THÁI BÀN */
        .table-tag {
            font-size: 0.7rem;
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .standard-card .table-tag { background: rgba(0, 243, 255, 0.15); color: #00f3ff; }
        .vip-card .table-tag { background: rgba(255, 170, 0, 0.15); color: #ffaa00; }

        /* --- THÂN BÀN CHƠI CHIA LÀM 3 KHỐI ĐỀU ĐẶN --- */
        .table-body {
            display: flex;
            height: 150px;
            background-color: #050811;
            position: relative;
        }

        /* KHỐI 1: Ô chứa Ảnh Dealer/Baccarat lớn */
        .table-avatar-container {
            width: 125px;
            height: 100%;
            border-right: 1px solid rgba(255,255,255,0.05);
            overflow: hidden;
            background: #090e1a;
        }

        .table-avatar-large {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.5s;
        }
        .table-card:hover .table-avatar-large {
            transform: scale(1.08);
        }

        /* KHỐI 2: Đồ thị / Hệ thống Lưới Cầu Baccarat chuẩn Casino */
        .chart-area {
            flex: 1;
            border-right: 1px solid rgba(255,255,255,0.05);
            padding: 6px;
            display: grid;
            grid-template-columns: repeat(11, 1fr);
            grid-template-rows: repeat(5, 1fr);
            gap: 4px;
            background: #03050a;
        }

        /* Các hạt chấm cầu tròn xịn sò */
        .bcr-bead {
            border-radius: 50%;
            width: 100%;
            height: 100%;
            max-width: 14px;
            max-height: 14px;
            margin: auto;
        }
        .bcr-bead.banker { background: #ff3838; box-shadow: 0 0 6px #ff3838; } /* Nhà Cái */
        .bcr-bead.player { background: #0084ff; box-shadow: 0 0 6px #0084ff; } /* Nhà Con */
        .bcr-bead.tie { background: #2ed573; box-shadow: 0 0 6px #2ed573; }    /* Hòa */
        .bcr-bead.empty { background: rgba(255,255,255,0.03); }

        /* CHỮ BÁO LỆNH NEON CHỚP TẮT */
        .status-alert {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(255, 0, 85, 0.95);
            color: #fff;
            font-weight: 900;
            font-size: 0.95rem;
            padding: 6px 16px;
            border-radius: 6px;
            letter-spacing: 1px;
            box-shadow: 0 0 15px #ff0055;
            animation: cyber-blink 1s infinite alternate;
            z-index: 5;
            pointer-events: none;
        }

        @keyframes cyber-blink {
            0% { opacity: 1; transform: translate(-50%, -50%) scale(1); }
            100% { opacity: 0.4; transform: translate(-50%, -50%) scale(0.96); }
        }

        /* KHỐI 3: Ô hiển thị Tỷ lệ thắng bên phải */
        .rate-area {
            width: 125px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 10px;
            background: linear-gradient(to right, #050811, #090e1a);
        }

        .rate-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            color: #697c9c;
            letter-spacing: 1px;
            margin-bottom: 6px;
        }

        .rate-value {
            font-size: 1.5rem;
            font-weight: 900;
            color: #ff0055;
            text-shadow: 0 0 10px rgba(255, 0, 85, 0.5);
            transition: color 0.5s ease;
        }
        /* Đổi màu tỷ lệ cực cao sang màu lục bảo */
        .rate-high {
            color: #00ff66 !important;
            text-shadow: 0 0 10px rgba(0, 255, 102, 0.5) !important;
        }

        /* FOOTER CỦA BÀN CHƠI */
        .table-footer {
            background: rgba(19, 26, 43, 0.4);
            padding: 12px 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid rgba(255,255,255,0.03);
        }

        /* Các đốm sáng chỉ báo tín hiệu mạng/trạng thái */
        .live-pulse {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.75rem;
            color: #697c9c;
        }
        .pulse-dot {
            width: 6px;
            height: 6px;
            background-color: #00ff66;
            border-radius: 50%;
            box-shadow: 0 0 8px #00ff66;
            animation: pulse-eff 1.5s infinite;
        }
        @keyframes pulse-eff {
            0% { transform: scale(0.8); opacity: 0.5; }
            50% { transform: scale(1.3); opacity: 1; }
            100% { transform: scale(0.8); opacity: 0.5; }
        }

        /* NÚT BẮT ĐẦU VIP */
        .btn-start {
            background: linear-gradient(90deg, #11192e, #1a2640);
            border: 1px solid #243757;
            color: #fff;
            padding: 8px 22px;
            font-size: 0.8rem;
            font-weight: 800;
            border-radius: 6px;
            cursor: pointer;
            text-transform: uppercase;
            transition: all 0.3s;
        }
        .standard-card .btn-start:hover {
            background: linear-gradient(90deg, #00f3ff, #0077ff);
            color: #000;
            border-color: #00f3ff;
            box-shadow: 0 0 12px rgba(0, 243, 255, 0.5);
        }
        .vip-card .btn-start:hover {
            background: linear-gradient(90deg, #ffaa00, #ff6600);
            color: #000;
            border-color: #ffaa00;
            box-shadow: 0 0 12px rgba(255, 170, 0, 0.5);
        }
    </style>
</head>
<body>

    <div class="dashboard-header">
        <?php
            $title = "LOBBY BACCARAT AI";
            if (strpos($gameParam, 'ae') !== false) $title = "SẢNH AE SEXY";
            if (strpos($gameParam, 'dg') !== false) $title = "SẢNH DREAMGAMING";
            if (strpos($gameParam, 'wm') !== false) $title = "SẢNH WM CASINO";
            if (strpos($gameParam, 'evo') !== false) $title = "SẢNH EVOLUTION";
        ?>
        <div class="brand-title"><?= $title ?></div>
        <div class="controls-wrapper">
            <a href="/index.php" class="filter-btn" style="text-decoration: none; display: inline-flex; align-items: center; gap: 5px; background: #0f172a; border-color: #3b82f6; color: #3b82f6;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M2 13.5V7h1v6.5a.5.5 0 0 0 .5.5h9a.5.5 0 0 0 .5-.5V7h1v6.5a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 2 13.5zm11-11V6l-2-2V2.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5z"/>
                    <path fill-rule="evenodd" d="M7.293 1.5a1 1 0 0 1 1.414 0l6.647 6.646a.5.5 0 0 1-.708.708L8 2.207 1.354 8.854a.5.5 0 1 1-.708-.708L7.293 1.5z"/>
                </svg>
                TRANG CHỦ
            </a>
            <input type="text" id="searchTable" class="search-box" placeholder="Tìm kiếm số bàn...">
            <button class="filter-btn active" onclick="filterTables('all', this)">TẤT CẢ</button>
            <button class="filter-btn" onclick="filterTables('standard', this)">BÀN THƯỜNG</button>
            <button class="filter-btn btn-vip-filter" onclick="filterTables('vip', this)">BÀN LOẠI C</button>
        </div>
    </div>

    <div class="lobby-grid" id="lobbyContainer"></div>

        <script>
        const API_URL = "/api/game_proxy.php";
        const gameParam = "<?= htmlspecialchars($gameParam) ?>";
        let tableData = [];
        let currentFilter = 'all';
        let searchQuery = '';

        async function fetchLobbyData() {
            try {
                const formData = new URLSearchParams();
                formData.append('action', 'history');
                formData.append('game', gameParam);

                const response = await fetch(API_URL, {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) throw new Error('Network error');
                
                const respJson = await response.json();
                const data = respJson.data ? JSON.parse(decodeURIComponent(escape(atob(respJson.data)))) : respJson;
                
                if (data && data.history && Array.isArray(data.history)) {
                    const uniqueTables = {};
                    data.history.forEach(item => {
                        if (!uniqueTables[item.table]) {
                            uniqueTables[item.table] = item;
                        }
                    });
                    const uniqueHistory = Object.values(uniqueTables);

                    tableData = uniqueHistory.map((item, index) => {
                        // Create a stable random number based on table string for winrate
                        let hash = 0;
                        const tStr = item.table.toString();
                        for (let i = 0; i < tStr.length; i++) {
                            hash = tStr.charCodeAt(i) + ((hash << 5) - hash);
                        }
                        const stableRandom = Math.abs(hash) % 21; // 0 to 20
                        const winRate = 80 + stableRandom;

                        return {
                            id: 'Bàn ' + item.table,
                            tableNum: item.table,
                            type: item.table.toString().startsWith('C') ? 'vip' : 'standard',
                            imgNum: (index % 16) + 1,
                            winRate: winRate,
                            alert: (Math.abs(hash * 3) % 100) > 75,
                            resultStr: item.result
                        };
                    });
                }
            } catch (error) {
                console.error("Fetch error:", error);
            }
            renderLobby();
        }

        function renderLobby() {
            const container = document.getElementById('lobbyContainer');
            container.innerHTML = '';

            tableData.forEach((table) => {
                if (currentFilter !== 'all' && table.type !== currentFilter) return;
                if (searchQuery && !table.id.toLowerCase().includes(searchQuery.toLowerCase())) return;

                const isVip = table.type === 'vip';
                const cardClass = isVip ? 'table-card vip-card' : 'table-card standard-card';
                const tagLabel = isVip ? 'PREMIUM VIP' : 'STANDARD';
                const rateClass = table.winRate >= 93 ? 'rate-value rate-high' : 'rate-value';

                let beadGridHTML = '';
                const results = (table.resultStr || '').split('');
                for (let b = 0; b < 55; b++) {
                    let beadType = 'empty';
                    if (b < results.length) {
                        const r = results[b];
                        if (r === 'B') beadType = 'banker';
                        else if (r === 'P') beadType = 'player';
                        else if (r === 'T') beadType = 'tie';
                    }
                    beadGridHTML += '<div class="bcr-bead ' + beadType + '"></div>';
                }

                const html = `
                    <div class="${cardClass}">
                        <div class="table-header">
                            <span class="table-title">${table.id}</span>
                            <span class="table-tag">${tagLabel}</span>
                        </div>
                        <div class="table-body">
                            <div class="table-avatar-container">
                                <img src="/assets/img/bcr${table.imgNum}.png" alt="${table.id}" class="table-avatar-large" onerror="this.src='https://via.placeholder.com/125x150/11192e/ffffff?text=BCR+${table.imgNum}'">
                            </div>
                            
                            ${table.alert ? '<div class="status-alert">VÀO LỆNH</div>' : ''}
                            
                            <div class="chart-area">
                                ${beadGridHTML}
                            </div>
                            
                            <div class="rate-area">
                                <span class="rate-label">Tỉ Lệ Thắng</span>
                                <span class="rate-value ${rateClass}">${table.winRate}%</span>
                            </div>
                        </div>
                        <div class="table-footer">
                            <div class="live-pulse">
                                <div class="pulse-dot"></div>
                                <span>Kết nối ổn định</span>
                            </div>
                            <button class="btn-start" onclick="window.location.href='predict/baccarat_predict.php?game=${gameParam}&table=${table.tableNum}'">Bắt đầu</button>
                        </div>
                    </div>
                `;
                container.innerHTML += html;
            });
        }

        function filterTables(type, element) {
            document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
            element.classList.add('active');
            currentFilter = type;
            renderLobby();
        }

        document.getElementById('searchTable').addEventListener('input', (e) => {
            searchQuery = e.target.value;
            renderLobby();
        });

        fetchLobbyData();
        setInterval(() => fetchLobbyData(), 5000);
    </script>
</body>
</html>

