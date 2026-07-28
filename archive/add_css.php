<?php
$css = <<<EOD

/* Form Container Styles */
.form-container {
    background: linear-gradient(135deg, rgba(30, 41, 59, 0.7), rgba(15, 23, 42, 0.9)); 
    backdrop-filter: blur(15px);
    -webkit-backdrop-filter: blur(15px);
    border: 1px solid rgba(255, 255, 255, 0.1); 
    border-top: 1px solid rgba(255, 255, 255, 0.2); 
    padding: 40px; 
    border-radius: 24px; 
    box-shadow: 0 10px 30px 0 rgba(0, 0, 0, 0.5); 
    width: 100%; 
    max-width: 700px;
    margin: 40px auto;
    color: #f8fafc;
    /* To account for the sidebar: */
    margin-left: max(auto, 100px);
}
@media (min-width: 1000px) {
    .form-container {
        margin-left: auto;
        margin-right: auto;
        transform: translateX(30px); /* slightly push it right because of the sidebar */
    }
}
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
.ticket-info { background: rgba(0,0,0,0.3); padding: 15px; border-radius: 12px; margin-bottom: 20px; border: 1px solid rgba(255,255,255,0.1); }
.ticket-info span { color: #94a3b8; }
.btn-group { display: flex; gap: 15px; margin-top: 30px; }
.btn-escalate, .btn-finish, .btn-close { 
    flex: 1; padding: 12px; border: none; border-radius: 8px; font-size: 1.1em; font-weight: bold; cursor: pointer; color: white; transition: background 0.3s;
}
.btn-escalate { background: #ea580c; } .btn-escalate:hover { background: #c2410c; }
.btn-finish { background: #10b981; } .btn-finish:hover { background: #059669; }
.action-card { background: rgba(15, 23, 42, 0.6); padding: 15px; border-radius: 12px; margin-bottom: 15px; border: 1px solid rgba(255,255,255,0.1); }

EOD;

// NOTE: Hardcoded path guarded — relative now. Dev script only.
file_put_contents('css/global.css', $css, FILE_APPEND);
echo "Added form-container CSS";
?>
