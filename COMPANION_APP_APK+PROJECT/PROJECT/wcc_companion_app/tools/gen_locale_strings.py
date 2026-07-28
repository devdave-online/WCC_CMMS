#!/usr/bin/env python3
"""Generate companion values-*/strings.xml for all 34 locales (web SoT catalog).

Sole author of this project: Project owner.
"""
from __future__ import annotations
from pathlib import Path
import html

RES = Path(__file__).resolve().parents[1] / "app" / "src" / "main" / "res"

# tag -> Android values folder suffix (None = default values/)
FOLDERS = {
    "en": None,
    "hi": "values-hi",
    "vi": "values-vi",
    "id": "values-in",  # Android historical code for Indonesian
    "bn": "values-bn",
    "ar": "values-ar",
    "ur": "values-ur",
    "fil": "values-fil",
    "fr": "values-fr",
    "es": "values-es",
    "ta": "values-ta",
    "te": "values-te",
    "mr": "values-mr",
    "gu": "values-gu",
    "kn": "values-kn",
    "ml": "values-ml",
    "pa": "values-pa",
    "ms": "values-ms",
    "de": "values-de",
    "pt": "values-pt",
    "pt-BR": "values-pt-rBR",
    "zh-Hans": "values-zh-rCN",
    "ru": "values-ru",
    "ja": "values-ja",
    "it": "values-it",
    "tr": "values-tr",
    "th": "values-th",
    "sw": "values-sw",
    "nl": "values-nl",
    "pl": "values-pl",
    "ha": "values-ha",
    "yo": "values-yo",
    "ig": "values-ig",
    "am": "values-am",
}

# English master (companion UI only — not full web catalog)
EN = {
    "app_name": "WCC Companion",
    "cd_help": "How to navigate",
    "cd_profile": "Profile",
    "cd_theme": "Toggle theme",
    "cd_language": "Language",
    "badge_live": "Live",
    "badge_syncing": "Syncing",
    "badge_offline": "Offline",
    "badge_off_plant": "Off-plant",
    "badge_offline_unsynced": "%1$s · %2$d unsynced",
    "badge_conflict": "Conflict · %1$d",
    "nav_swipe_warn": "Navigation is by swiping — tap ? to learn the gestures.",
    "language_picker_title": "Language",
    "rail_tickets": "Tickets",
    "rail_work_orders": "Work Orders",
    "rail_equipment": "Equipment",
    "rail_toolings": "Toolings",
    "rail_inventory": "Inventory",
    "rail_history": "History",
    "rail_tickets_desc": "Active maintenance tickets requiring immediate technician intervention.",
    "rail_work_orders_desc": "Scheduled corrective tasks and assigned maintenance activities.",
    "rail_equipment_desc": "Asset registry — search, scan tags, inspect machine info.",
    "rail_toolings_desc": "Tooling registry — same pattern as Equipment.",
    "rail_inventory_desc": "Parts stock levels and locations.",
    "rail_history_desc": "Past closed events and completed work orders.",
    "login_title": "WCC Companion",
    "login_server": "Server",
    "login_username": "Username",
    "login_password": "Password",
    "login_button": "Sign in",
    "login_server_hint": "e.g. 192.168.0.152",
    "biometric_gate_title": "App locked on this device",
    "biometric_gate_body": "Fingerprint, face, or device PIN unlocks the floor app. This setting never leaves the handset.",
    "biometric_unlock": "Unlock",
    "biometric_use_password": "Use password instead",
    "mine_all": "All · %1$d",
    "mine_mine": "Mine · %1$d",
    "outbox_title": "Sync queue",
    "outbox_retry": "Retry now",
    "outbox_close": "Close",
}

# Per-locale overrides (native UI strings). Keys missing fall back to EN.
# Focus: floor chrome + login; language names live in AppLocale enum (native).
T: dict[str, dict[str, str]] = {
    "hi": {
        "app_name": "WCC कंपैनियन",
        "cd_help": "नेविगेट कैसे करें",
        "cd_profile": "प्रोफ़ाइल",
        "cd_theme": "थीम बदलें",
        "cd_language": "भाषा",
        "badge_live": "लाइव",
        "badge_syncing": "सिंक हो रहा है",
        "badge_offline": "ऑफ़लाइन",
        "badge_off_plant": "प्लांट से बाहर",
        "badge_offline_unsynced": "%1$s · %2$d अनसिंक",
        "badge_conflict": "टकराव · %1$d",
        "nav_swipe_warn": "नेविगेशन स्वाइप से होता है — जेस्चर सीखने के लिए ? टैप करें।",
        "language_picker_title": "भाषा",
        "rail_tickets": "टिकट",
        "rail_work_orders": "वर्क ऑर्डर",
        "rail_equipment": "उपकरण",
        "rail_toolings": "टूलींग",
        "rail_inventory": "इन्वेंटरी",
        "rail_history": "इतिहास",
        "rail_tickets_desc": "तत्काल तकनीशियन हस्तक्षेप की आवश्यकता वाले सक्रिय रखरखाव टिकट।",
        "rail_work_orders_desc": "निर्धारित सुधारात्मक कार्य और असाइन किए गए रखरखाव।",
        "rail_equipment_desc": "संपत्ति रजिस्ट्री — खोजें, टैग स्कैन करें, मशीन जानकारी देखें।",
        "rail_toolings_desc": "टूलींग रजिस्ट्री — उपकरण जैसा पैटर्न।",
        "rail_inventory_desc": "पार्ट स्टॉक स्तर और स्थान।",
        "rail_history_desc": "पिछली बंद घटनाएँ और पूर्ण वर्क ऑर्डर।",
        "login_title": "WCC कंपैनियन",
        "login_server": "सर्वर",
        "login_username": "उपयोगकर्ता नाम",
        "login_password": "पासवर्ड",
        "login_button": "साइन इन",
        "login_server_hint": "उदा. 192.168.0.152",
        "biometric_gate_title": "इस डिवाइस पर ऐप लॉक है",
        "biometric_gate_body": "फ़िंगरप्रिंट, चेहरा या डिवाइस PIN फ़्लोर ऐप खोलता है। यह सेटिंग हैंडसेट पर ही रहती है।",
        "biometric_unlock": "अनलॉक",
        "biometric_use_password": "इसके बजाय पासवर्ड उपयोग करें",
        "mine_all": "सभी · %1$d",
        "mine_mine": "मेरे · %1$d",
        "outbox_title": "सिंक कतार",
        "outbox_retry": "अभी पुनः प्रयास",
        "outbox_close": "बंद करें",
    },
    "vi": {
        "app_name": "WCC Companion",
        "cd_help": "Cách điều hướng",
        "cd_profile": "Hồ sơ",
        "cd_theme": "Đổi giao diện",
        "cd_language": "Ngôn ngữ",
        "badge_live": "Trực tuyến",
        "badge_syncing": "Đang đồng bộ",
        "badge_offline": "Ngoại tuyến",
        "badge_off_plant": "Ngoài nhà máy",
        "badge_offline_unsynced": "%1$s · %2$d chưa đồng bộ",
        "badge_conflict": "Xung đột · %1$d",
        "nav_swipe_warn": "Điều hướng bằng vuốt — chạm ? để học cử chỉ.",
        "language_picker_title": "Ngôn ngữ",
        "rail_tickets": "Phiếu",
        "rail_work_orders": "Lệnh công việc",
        "rail_equipment": "Thiết bị",
        "rail_toolings": "Dụng cụ",
        "rail_inventory": "Kho",
        "rail_history": "Lịch sử",
        "rail_tickets_desc": "Phiếu bảo trì đang mở cần kỹ thuật viên can thiệp ngay.",
        "rail_work_orders_desc": "Công việc sửa chữa theo lịch và bảo trì được giao.",
        "rail_equipment_desc": "Sổ tài sản — tìm, quét tem, xem thông tin máy.",
        "rail_toolings_desc": "Sổ dụng cụ — cùng kiểu với thiết bị.",
        "rail_inventory_desc": "Tồn kho linh kiện và vị trí.",
        "rail_history_desc": "Sự kiện đã đóng và lệnh đã hoàn thành.",
        "login_title": "WCC Companion",
        "login_server": "Máy chủ",
        "login_username": "Tên đăng nhập",
        "login_password": "Mật khẩu",
        "login_button": "Đăng nhập",
        "login_server_hint": "vd. 192.168.0.152",
        "biometric_gate_title": "Ứng dụng đã khóa trên thiết bị này",
        "biometric_gate_body": "Vân tay, khuôn mặt hoặc mã PIN mở khóa. Cài đặt chỉ lưu trên máy.",
        "biometric_unlock": "Mở khóa",
        "biometric_use_password": "Dùng mật khẩu",
        "mine_all": "Tất cả · %1$d",
        "mine_mine": "Của tôi · %1$d",
        "outbox_title": "Hàng đợi đồng bộ",
        "outbox_retry": "Thử lại ngay",
        "outbox_close": "Đóng",
    },
    "id": {
        "app_name": "WCC Companion",
        "cd_help": "Cara navigasi",
        "cd_profile": "Profil",
        "cd_theme": "Ganti tema",
        "cd_language": "Bahasa",
        "badge_live": "Langsung",
        "badge_syncing": "Menyinkronkan",
        "badge_offline": "Luring",
        "badge_off_plant": "Di luar pabrik",
        "badge_offline_unsynced": "%1$s · %2$d belum sinkron",
        "badge_conflict": "Konflik · %1$d",
        "nav_swipe_warn": "Navigasi dengan menggeser — ketuk ? untuk mempelajari gestur.",
        "language_picker_title": "Bahasa",
        "rail_tickets": "Tiket",
        "rail_work_orders": "Perintah Kerja",
        "rail_equipment": "Peralatan",
        "rail_toolings": "Perkakas",
        "rail_inventory": "Inventaris",
        "rail_history": "Riwayat",
        "rail_tickets_desc": "Tiket pemeliharaan aktif yang butuh intervensi teknisi segera.",
        "rail_work_orders_desc": "Tugas korektif terjadwal dan pemeliharaan yang ditugaskan.",
        "rail_equipment_desc": "Registri aset — cari, pindai tag, lihat info mesin.",
        "rail_toolings_desc": "Registri perkakas — pola sama dengan Peralatan.",
        "rail_inventory_desc": "Stok suku cadang dan lokasi.",
        "rail_history_desc": "Peristiwa tertutup dan perintah kerja selesai.",
        "login_title": "WCC Companion",
        "login_server": "Server",
        "login_username": "Nama pengguna",
        "login_password": "Kata sandi",
        "login_button": "Masuk",
        "login_server_hint": "mis. 192.168.0.152",
        "biometric_gate_title": "Aplikasi terkunci di perangkat ini",
        "biometric_gate_body": "Sidik jari, wajah, atau PIN membuka aplikasi lantai. Pengaturan hanya di perangkat.",
        "biometric_unlock": "Buka kunci",
        "biometric_use_password": "Gunakan kata sandi",
        "mine_all": "Semua · %1$d",
        "mine_mine": "Milik saya · %1$d",
        "outbox_title": "Antrian sinkron",
        "outbox_retry": "Coba lagi",
        "outbox_close": "Tutup",
    },
}

# Reuse strong packs for related languages where full custom set not needed;
# still distinct files so Android can switch. Partial overrides below.
COMMON_EURO = {
    "fr": {
        "cd_help": "Comment naviguer", "cd_profile": "Profil", "cd_theme": "Changer le thème",
        "cd_language": "Langue", "badge_live": "En ligne", "badge_syncing": "Sync…",
        "badge_offline": "Hors ligne", "badge_off_plant": "Hors usine",
        "badge_offline_unsynced": "%1$s · %2$d non sync", "badge_conflict": "Conflit · %1$d",
        "nav_swipe_warn": "Navigation par balayage — touchez ? pour les gestes.",
        "language_picker_title": "Langue", "rail_tickets": "Tickets",
        "rail_work_orders": "Ordres de travail", "rail_equipment": "Équipement",
        "rail_toolings": "Outillage", "rail_inventory": "Stock", "rail_history": "Historique",
        "rail_tickets_desc": "Tickets de maintenance actifs nécessitant une intervention immédiate.",
        "rail_work_orders_desc": "Tâches correctives planifiées et maintenance assignée.",
        "rail_equipment_desc": "Registre des actifs — rechercher, scanner, inspecter.",
        "rail_toolings_desc": "Registre d'outillage — même modèle que l'équipement.",
        "rail_inventory_desc": "Niveaux de stock et emplacements.",
        "rail_history_desc": "Événements clôturés et OT terminés.",
        "login_server": "Serveur", "login_username": "Identifiant",
        "login_password": "Mot de passe", "login_button": "Connexion",
        "biometric_gate_title": "App verrouillée sur cet appareil",
        "biometric_gate_body": "Empreinte, visage ou code déverrouille l'app. Réglage local uniquement.",
        "biometric_unlock": "Déverrouiller", "biometric_use_password": "Utiliser le mot de passe",
        "mine_all": "Tous · %1$d", "mine_mine": "Mien · %1$d",
        "outbox_title": "File de sync", "outbox_retry": "Réessayer", "outbox_close": "Fermer",
    },
    "es": {
        "cd_help": "Cómo navegar", "cd_profile": "Perfil", "cd_theme": "Cambiar tema",
        "cd_language": "Idioma", "badge_live": "En vivo", "badge_syncing": "Sincronizando",
        "badge_offline": "Sin conexión", "badge_off_plant": "Fuera de planta",
        "badge_offline_unsynced": "%1$s · %2$d sin sync", "badge_conflict": "Conflicto · %1$d",
        "nav_swipe_warn": "La navegación es por deslizamiento — toque ? para aprender gestos.",
        "language_picker_title": "Idioma", "rail_tickets": "Tickets",
        "rail_work_orders": "Órdenes de trabajo", "rail_equipment": "Equipos",
        "rail_toolings": "Herramientas", "rail_inventory": "Inventario", "rail_history": "Historial",
        "rail_tickets_desc": "Tickets de mantenimiento activos que requieren intervención inmediata.",
        "rail_work_orders_desc": "Tareas correctivas programadas y mantenimiento asignado.",
        "rail_equipment_desc": "Registro de activos — buscar, escanear, inspeccionar.",
        "rail_toolings_desc": "Registro de herramientas — mismo patrón que equipos.",
        "rail_inventory_desc": "Niveles de stock y ubicaciones.",
        "rail_history_desc": "Eventos cerrados y OT completadas.",
        "login_server": "Servidor", "login_username": "Usuario",
        "login_password": "Contraseña", "login_button": "Iniciar sesión",
        "biometric_gate_title": "App bloqueada en este dispositivo",
        "biometric_gate_body": "Huella, rostro o PIN desbloquean. Solo en este teléfono.",
        "biometric_unlock": "Desbloquear", "biometric_use_password": "Usar contraseña",
        "mine_all": "Todos · %1$d", "mine_mine": "Míos · %1$d",
        "outbox_title": "Cola de sync", "outbox_retry": "Reintentar", "outbox_close": "Cerrar",
    },
    "de": {
        "cd_help": "Navigation", "cd_profile": "Profil", "cd_theme": "Design wechseln",
        "cd_language": "Sprache", "badge_live": "Live", "badge_syncing": "Sync…",
        "badge_offline": "Offline", "badge_off_plant": "Außerhalb Anlage",
        "badge_offline_unsynced": "%1$s · %2$d offen", "badge_conflict": "Konflikt · %1$d",
        "nav_swipe_warn": "Navigation per Wischen — tippen Sie ? für Gesten.",
        "language_picker_title": "Sprache", "rail_tickets": "Tickets",
        "rail_work_orders": "Arbeitsaufträge", "rail_equipment": "Anlagen",
        "rail_toolings": "Werkzeuge", "rail_inventory": "Lager", "rail_history": "Verlauf",
        "rail_tickets_desc": "Aktive Wartungstickets mit sofortigem Handlungsbedarf.",
        "rail_work_orders_desc": "Geplante Korrekturaufgaben und zugewiesene Wartung.",
        "rail_equipment_desc": "Anlagenregister — suchen, scannen, prüfen.",
        "rail_toolings_desc": "Werkzeugregister — gleiches Muster wie Anlagen.",
        "rail_inventory_desc": "Bestände und Lagerorte.",
        "rail_history_desc": "Geschlossene Ereignisse und erledigte AAs.",
        "login_server": "Server", "login_username": "Benutzername",
        "login_password": "Passwort", "login_button": "Anmelden",
        "biometric_gate_title": "App auf diesem Gerät gesperrt",
        "biometric_gate_body": "Fingerabdruck, Gesicht oder PIN entsperren. Nur lokal.",
        "biometric_unlock": "Entsperren", "biometric_use_password": "Passwort verwenden",
        "mine_all": "Alle · %1$d", "mine_mine": "Meine · %1$d",
        "outbox_title": "Sync-Warteschlange", "outbox_retry": "Erneut versuchen", "outbox_close": "Schließen",
    },
    "ar": {
        "cd_help": "كيفية التنقل", "cd_profile": "الملف", "cd_theme": "تبديل السمة",
        "cd_language": "اللغة", "badge_live": "مباشر", "badge_syncing": "مزامنة",
        "badge_offline": "غير متصل", "badge_off_plant": "خارج المصنع",
        "badge_offline_unsynced": "%1$s · %2$d غير مزامن", "badge_conflict": "تعارض · %1$d",
        "nav_swipe_warn": "التنقل بالسحب — اضغط ؟ لتعلم الإيماءات.",
        "language_picker_title": "اللغة", "rail_tickets": "التذاكر",
        "rail_work_orders": "أوامر العمل", "rail_equipment": "المعدات",
        "rail_toolings": "الأدوات", "rail_inventory": "المخزون", "rail_history": "السجل",
        "rail_tickets_desc": "تذاكر صيانة نشطة تتطلب تدخلاً فورياً.",
        "rail_work_orders_desc": "مهام تصحيحية مجدولة وصيانة مسندة.",
        "rail_equipment_desc": "سجل الأصول — بحث، مسح، فحص.",
        "rail_toolings_desc": "سجل الأدوات — نفس نمط المعدات.",
        "rail_inventory_desc": "مستويات المخزون والمواقع.",
        "rail_history_desc": "أحداث مغلقة وأوامر مكتملة.",
        "login_server": "الخادم", "login_username": "اسم المستخدم",
        "login_password": "كلمة المرور", "login_button": "تسجيل الدخول",
        "biometric_gate_title": "التطبيق مقفل على هذا الجهاز",
        "biometric_gate_body": "البصمة أو الوجه أو رمز الجهاز يفتح التطبيق. الإعداد محلي فقط.",
        "biometric_unlock": "فتح القفل", "biometric_use_password": "استخدم كلمة المرور",
        "mine_all": "الكل · %1$d", "mine_mine": "خاصتي · %1$d",
        "outbox_title": "قائمة المزامنة", "outbox_retry": "أعد المحاولة", "outbox_close": "إغلاق",
    },
    "zh-Hans": {
        "cd_help": "如何导航", "cd_profile": "个人资料", "cd_theme": "切换主题",
        "cd_language": "语言", "badge_live": "在线", "badge_syncing": "同步中",
        "badge_offline": "离线", "badge_off_plant": "厂区外",
        "badge_offline_unsynced": "%1$s · %2$d 未同步", "badge_conflict": "冲突 · %1$d",
        "nav_swipe_warn": "通过滑动导航 — 点按 ? 学习手势。",
        "language_picker_title": "语言", "rail_tickets": "工单",
        "rail_work_orders": "工作指令", "rail_equipment": "设备",
        "rail_toolings": "工装", "rail_inventory": "库存", "rail_history": "历史",
        "rail_tickets_desc": "需要立即处理的活动维护工单。",
        "rail_work_orders_desc": "计划纠正任务与已分配维护。",
        "rail_equipment_desc": "资产登记 — 搜索、扫码、查看机器信息。",
        "rail_toolings_desc": "工装登记 — 与设备相同模式。",
        "rail_inventory_desc": "零件库存与库位。",
        "rail_history_desc": "已关闭事件与已完成工作指令。",
        "login_server": "服务器", "login_username": "用户名",
        "login_password": "密码", "login_button": "登录",
        "biometric_gate_title": "此设备上应用已锁定",
        "biometric_gate_body": "指纹、面容或设备 PIN 解锁。设置仅保存在本机。",
        "biometric_unlock": "解锁", "biometric_use_password": "改用密码",
        "mine_all": "全部 · %1$d", "mine_mine": "我的 · %1$d",
        "outbox_title": "同步队列", "outbox_retry": "立即重试", "outbox_close": "关闭",
    },
    "ja": {
        "cd_help": "操作方法", "cd_profile": "プロフィール", "cd_theme": "テーマ切替",
        "cd_language": "言語", "badge_live": "ライブ", "badge_syncing": "同期中",
        "badge_offline": "オフライン", "badge_off_plant": "工場外",
        "badge_offline_unsynced": "%1$s · 未同期 %2$d", "badge_conflict": "競合 · %1$d",
        "nav_swipe_warn": "スワイプで操作 — ? をタップしてジェスチャを確認。",
        "language_picker_title": "言語", "rail_tickets": "チケット",
        "rail_work_orders": "作業指示", "rail_equipment": "設備",
        "rail_toolings": "ツーリング", "rail_inventory": "在庫", "rail_history": "履歴",
        "rail_tickets_desc": "即時対応が必要な稼働中メンテナンチケット。",
        "rail_work_orders_desc": "予定是正作業と割当メンテナンス。",
        "rail_equipment_desc": "資産台帳 — 検索・スキャン・確認。",
        "rail_toolings_desc": "ツーリング台帳 — 設備と同じ操作。",
        "rail_inventory_desc": "部品在庫と保管場所。",
        "rail_history_desc": "クローズ済みイベントと完了 WO。",
        "login_server": "サーバー", "login_username": "ユーザー名",
        "login_password": "パスワード", "login_button": "サインイン",
        "biometric_gate_title": "この端末でアプリがロックされています",
        "biometric_gate_body": "指紋・顔・PIN で解除。設定はこの端末のみ。",
        "biometric_unlock": "解除", "biometric_use_password": "パスワードを使う",
        "mine_all": "すべて · %1$d", "mine_mine": "自分 · %1$d",
        "outbox_title": "同期キュー", "outbox_retry": "再試行", "outbox_close": "閉じる",
    },
    "pt": {
        "cd_help": "Como navegar", "cd_profile": "Perfil", "cd_theme": "Alternar tema",
        "cd_language": "Idioma", "badge_live": "Ao vivo", "badge_syncing": "A sincronizar",
        "badge_offline": "Offline", "badge_off_plant": "Fora da planta",
        "badge_offline_unsynced": "%1$s · %2$d por sync", "badge_conflict": "Conflito · %1$d",
        "nav_swipe_warn": "Navegação por deslize — toque ? para os gestos.",
        "language_picker_title": "Idioma", "rail_tickets": "Tickets",
        "rail_work_orders": "Ordens de trabalho", "rail_equipment": "Equipamentos",
        "rail_toolings": "Ferramentas", "rail_inventory": "Inventário", "rail_history": "Histórico",
        "rail_tickets_desc": "Tickets de manutenção ativos com intervenção imediata.",
        "rail_work_orders_desc": "Tarefas corretivas agendadas e manutenção atribuída.",
        "rail_equipment_desc": "Registo de ativos — pesquisar, ler tags, inspecionar.",
        "rail_toolings_desc": "Registo de ferramentas — mesmo padrão dos equipamentos.",
        "rail_inventory_desc": "Níveis de stock e localizações.",
        "rail_history_desc": "Eventos fechados e OT concluídas.",
        "login_server": "Servidor", "login_username": "Utilizador",
        "login_password": "Palavra-passe", "login_button": "Entrar",
        "biometric_gate_title": "App bloqueada neste dispositivo",
        "biometric_gate_body": "Impressão digital, rosto ou PIN. Definição só neste telemóvel.",
        "biometric_unlock": "Desbloquear", "biometric_use_password": "Usar palavra-passe",
        "mine_all": "Todos · %1$d", "mine_mine": "Meus · %1$d",
        "outbox_title": "Fila de sync", "outbox_retry": "Tentar de novo", "outbox_close": "Fechar",
    },
    "ru": {
        "cd_help": "Как перемещаться", "cd_profile": "Профиль", "cd_theme": "Сменить тему",
        "cd_language": "Язык", "badge_live": "Онлайн", "badge_syncing": "Синхр…",
        "badge_offline": "Офлайн", "badge_off_plant": "Вне завода",
        "badge_offline_unsynced": "%1$s · %2$d не синхр.", "badge_conflict": "Конфликт · %1$d",
        "nav_swipe_warn": "Навигация свайпами — нажмите ?, чтобы изучить жесты.",
        "language_picker_title": "Язык", "rail_tickets": "Заявки",
        "rail_work_orders": "Наряды", "rail_equipment": "Оборудование",
        "rail_toolings": "Оснастка", "rail_inventory": "Склад", "rail_history": "История",
        "rail_tickets_desc": "Активные заявки, требующие срочного вмешательства.",
        "rail_work_orders_desc": "Плановые работы и назначенное ТО.",
        "rail_equipment_desc": "Реестр активов — поиск, сканирование, осмотр.",
        "rail_toolings_desc": "Реестр оснастки — как оборудование.",
        "rail_inventory_desc": "Остатки и ячейки.",
        "rail_history_desc": "Закрытые события и завершённые наряды.",
        "login_server": "Сервер", "login_username": "Логин",
        "login_password": "Пароль", "login_button": "Войти",
        "biometric_gate_title": "Приложение заблокировано на устройстве",
        "biometric_gate_body": "Отпечаток, лицо или PIN. Настройка только на этом телефоне.",
        "biometric_unlock": "Разблокировать", "biometric_use_password": "Войти паролем",
        "mine_all": "Все · %1$d", "mine_mine": "Мои · %1$d",
        "outbox_title": "Очередь синхр.", "outbox_retry": "Повторить", "outbox_close": "Закрыть",
    },
}


def merge(loc: str) -> dict[str, str]:
    out = dict(EN)
    if loc in T:
        out.update(T[loc])
    if loc in COMMON_EURO:
        out.update(COMMON_EURO[loc])
    # aliases / close relatives
    if loc == "pt-BR" and "pt" in COMMON_EURO:
        out.update(COMMON_EURO["pt"])
        out["login_username"] = "Usuário"
        out["login_password"] = "Senha"
    if loc == "ms" and "id" in T:
        out.update(T["id"])
        out["cd_language"] = "Bahasa"
        out["language_picker_title"] = "Bahasa"
    if loc == "ur":
        out.update(COMMON_EURO.get("ar", {}))
        out["language_picker_title"] = "زبان"
        out["cd_language"] = "زبان"
    if loc in ("bn", "ta", "te", "mr", "gu", "kn", "ml", "pa") and loc not in T:
        # Indian regional: keep structure from Hindi where available
        if "hi" in T:
            out.update({k: v for k, v in T["hi"].items() if k.startswith(("badge_", "login_", "mine_", "outbox_", "biometric_"))})
        labels = {
            "bn": ("টিকিট", "ওয়ার্ক অর্ডার", "যন্ত্রপাতি", "টুলিং", "ইনভেন্টরি", "ইতিহাস", "ভাষা"),
            "ta": ("டிக்கெட்டுகள்", "பணி ஆணைகள்", "உபகரணம்", "கருவிகள்", "சரக்கு", "வரலாறு", "மொழி"),
            "te": ("టికెట్లు", "వర్క్ ఆర్డర్లు", "పరికరాలు", "టూలింగ్", "ఇన్వెంటరీ", "చరిత్ర", "భాష"),
            "mr": ("तिकीट", "वर्क ऑर्डर", "उपकरणे", "टूलिंग", "इन्व्हेंटरी", "इतिहास", "भाषा"),
            "gu": ("ટિકિટ", "વર્ક ઓર્ડર", "સાધનો", "ટૂલિંગ", "ઇન્વેન્ટરી", "ઇતિહાસ", "ભાષા"),
            "kn": ("ಟಿಕೆಟ್‌ಗಳು", "ಕೆಲಸದ ಆದೇಶಗಳು", "ಉಪಕರಣ", "ಟೂಲಿಂಗ್", "ದಾಸ್ತಾನು", "ಇತಿಹಾಸ", "ಭಾಷೆ"),
            "ml": ("ടിക്കറ്റുകൾ", "വർക്ക് ഓർഡറുകൾ", "ഉപകരണം", "ടൂളിംഗ്", "ഇൻവെന്ററി", "ചരിത്രം", "ഭാഷ"),
            "pa": ("ਟਿਕਟਾਂ", "ਵਰਕ ਆਰਡਰ", "ਉਪਕਰਣ", "ਟੂਲਿੰਗ", "ਇਨਵੈਂਟਰੀ", "ਇਤਿਹਾਸ", "ਭਾਸ਼ਾ"),
        }
        if loc in labels:
            a, b, c, d, e, f, g = labels[loc]
            out.update({
                "rail_tickets": a, "rail_work_orders": b, "rail_equipment": c,
                "rail_toolings": d, "rail_inventory": e, "rail_history": f,
                "cd_language": g, "language_picker_title": g,
            })
    if loc in ("fil", "sw", "ha", "yo", "ig", "am", "th", "tr", "it", "nl", "pl") and loc not in T and loc not in COMMON_EURO:
        # Lightweight native chrome labels; rest EN fallback for floor terms
        lite = {
            "fil": {"cd_language": "Wika", "language_picker_title": "Wika", "login_button": "Mag-sign in",
                    "rail_tickets": "Mga Ticket", "rail_work_orders": "Mga Work Order", "badge_live": "Live",
                    "badge_offline": "Offline", "biometric_unlock": "I-unlock", "outbox_close": "Isara"},
            "sw": {"cd_language": "Lugha", "language_picker_title": "Lugha", "login_button": "Ingia",
                   "rail_tickets": "Tiketi", "rail_work_orders": "Agizo la kazi", "badge_live": "Moja kwa moja",
                   "badge_offline": "Nje ya mtandao", "biometric_unlock": "Fungua", "outbox_close": "Funga"},
            "th": {"cd_language": "ภาษา", "language_picker_title": "ภาษา", "login_button": "เข้าสู่ระบบ",
                   "rail_tickets": "ตั๋วงาน", "rail_work_orders": "ใบสั่งงาน", "badge_live": "ออนไลน์",
                   "badge_offline": "ออฟไลน์", "biometric_unlock": "ปลดล็อก", "outbox_close": "ปิด"},
            "tr": {"cd_language": "Dil", "language_picker_title": "Dil", "login_button": "Giriş",
                   "rail_tickets": "Biletler", "rail_work_orders": "İş emirleri", "badge_live": "Canlı",
                   "badge_offline": "Çevrimdışı", "biometric_unlock": "Kilidi aç", "outbox_close": "Kapat"},
            "it": {"cd_language": "Lingua", "language_picker_title": "Lingua", "login_button": "Accedi",
                   "rail_tickets": "Ticket", "rail_work_orders": "Ordini di lavoro", "badge_live": "Live",
                   "badge_offline": "Offline", "biometric_unlock": "Sblocca", "outbox_close": "Chiudi"},
            "nl": {"cd_language": "Taal", "language_picker_title": "Taal", "login_button": "Aanmelden",
                   "rail_tickets": "Tickets", "rail_work_orders": "Werkorders", "badge_live": "Live",
                   "badge_offline": "Offline", "biometric_unlock": "Ontgrendelen", "outbox_close": "Sluiten"},
            "pl": {"cd_language": "Język", "language_picker_title": "Język", "login_button": "Zaloguj",
                   "rail_tickets": "Zgłoszenia", "rail_work_orders": "Zlecenia", "badge_live": "Na żywo",
                   "badge_offline": "Offline", "biometric_unlock": "Odblokuj", "outbox_close": "Zamknij"},
            "ha": {"cd_language": "Harshe", "language_picker_title": "Harshe", "login_button": "Shiga",
                   "rail_tickets": "Tikiti", "rail_work_orders": "Umarnin aiki", "badge_live": "Kai tsaye",
                   "badge_offline": "Offline", "biometric_unlock": "Buɗe", "outbox_close": "Rufe"},
            "yo": {"cd_language": "Èdè", "language_picker_title": "Èdè", "login_button": "Wọlé",
                   "rail_tickets": "Tíkẹ́ẹ̀tì", "rail_work_orders": "Àṣẹ iṣẹ́", "badge_live": "Láàyè",
                   "badge_offline": "Àìsí lórí ayélujára", "biometric_unlock": "Ṣí i", "outbox_close": "Padé"},
            "ig": {"cd_language": "Asụsụ", "language_picker_title": "Asụsụ", "login_button": "Banye",
                   "rail_tickets": "Tiketi", "rail_work_orders": "Iwu ọrụ", "badge_live": "Dị ndụ",
                   "badge_offline": "Offline", "biometric_unlock": "Meghee", "outbox_close": "Mechie"},
            "am": {"cd_language": "ቋንቋ", "language_picker_title": "ቋንቋ", "login_button": "ግባ",
                   "rail_tickets": "ትኬቶች", "rail_work_orders": "የስራ ትዕዛዞች", "badge_live": "ቀጥታ",
                   "badge_offline": "ከመስመር ውጭ", "biometric_unlock": "ክፈት", "outbox_close": "ዝጋ"},
        }
        out.update(lite.get(loc, {}))
    return out


def escape_xml(s: str) -> str:
    return (
        s.replace("&", "&amp;")
        .replace("<", "&lt;")
        .replace(">", "&gt;")
        .replace('"', "\\\"")
        .replace("'", "\\'")
        .replace("\n", "\\n")
    )


def write_xml(folder: Path, strings: dict[str, str], locale: str) -> None:
    folder.mkdir(parents=True, exist_ok=True)
    lines = [
        '<?xml version="1.0" encoding="utf-8"?>',
        f"<!-- WCC Companion · locale={locale} · generated from tools/gen_locale_strings.py -->",
        "<resources>",
    ]
    for k, v in strings.items():
        lines.append(f'    <string name="{k}">{escape_xml(v)}</string>')
    lines.append("</resources>")
    lines.append("")
    (folder / "strings.xml").write_text("\n".join(lines), encoding="utf-8")


def main() -> None:
    assert len(FOLDERS) == 34, len(FOLDERS)
    for loc, folder_name in FOLDERS.items():
        folder = RES / "values" if folder_name is None else RES / folder_name
        write_xml(folder, merge(loc), loc)
        print(f"wrote {folder.relative_to(RES)} ({loc})")
    print("OK", len(FOLDERS), "locales")


if __name__ == "__main__":
    main()
