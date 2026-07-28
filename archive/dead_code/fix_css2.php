<?php
$css = <<<EOD

/* Action Buttons for Tables */
.action-btn {
    display: inline-block;
    padding: 8px 16px;
    font-size: 0.9em;
    font-weight: bold;
    border-radius: 8px;
    text-decoration: none;
    text-align: center;
    transition: all 0.2s ease-in-out;
    border: 1px solid transparent;
}
.btn-take {
    background: linear-gradient(135deg, #1e3a8a, #312e81);
    color: #e0e7ff;
    border-color: #4f46e5;
    box-shadow: 0 4px 10px rgba(49, 46, 129, 0.4);
}
.btn-take:hover {
    background: linear-gradient(135deg, #312e81, #4338ca);
    color: #ffffff;
    box-shadow: 0 6px 15px rgba(67, 56, 202, 0.6);
    transform: translateY(-2px);
}
.btn-close {
    background: linear-gradient(135deg, #475569, #334155);
    color: #f8fafc;
    border-color: #64748b;
    box-shadow: 0 4px 10px rgba(51, 65, 85, 0.4);
}
.btn-close:hover {
    background: linear-gradient(135deg, #334155, #1e293b);
    color: #ffffff;
    border-color: #94a3b8;
    box-shadow: 0 6px 15px rgba(71, 85, 105, 0.6);
    transform: translateY(-2px);
}

/* Fix Tooltip/Input formatting */
.auth-container input, .auth-container select, .auth-container textarea,
.form-container input, .form-container select, .form-container textarea {
    display: block !important;
    width: 100% !important;
    padding: 12px !important;
    margin-top: 8px !important;
    margin-bottom: 20px !important;
    border: 1px solid rgba(255,255,255,0.2) !important;
    background: rgba(15,23,42,0.6) !important;
    color: #f8fafc !important;
    border-radius: 8px !important;
    box-sizing: border-box !important;
    font-family: inherit;
}
.form-container label {
    display: block;
    font-weight: 600;
    margin-top: 10px;
    color: var(--text-accent);
}
.form-container select option {
    background: #1e293b;
    color: #f8fafc;
}
EOD;

file_put_contents('c:\xampp\htdocs\css\global.css', "\n" . $css, FILE_APPEND);
echo "Added action-btn and input fixes.";
?>

