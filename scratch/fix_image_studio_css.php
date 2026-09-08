<?php
$aiPhp = file_get_contents(__DIR__ . '/../student/ai.php');

$imgCss = <<< 'EOD'

        /* ===== VIEW 3: AI IMAGE GENERATOR STUDIO ===== */
        .ai-image-view {
            display: none;
            flex: 1;
            overflow: hidden;
            padding: 20px 24px;
            gap: 20px;
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            box-sizing: border-box;
            height: calc(100vh - 120px);
        }
        @media (max-width: 960px) {
            .ai-image-view {
                flex-direction: column;
                height: auto;
                overflow-y: auto;
                padding: 14px;
            }
        }
        .ai-img-left-panel {
            width: 440px;
            max-width: 100%;
            display: flex;
            flex-direction: column;
            gap: 16px;
            background: #ffffff;
            border: 1.5px solid var(--ai-border);
            border-radius: 18px;
            padding: 22px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            overflow-y: auto;
            flex-shrink: 0;
        }
        .ai-img-right-panel {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 16px;
            overflow-y: auto;
        }
        .ai-img-textarea {
            width: 100%;
            height: 110px;
            padding: 14px;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            font-size: 13.5px;
            font-family: inherit;
            color: #0f172a;
            outline: none;
            resize: none;
            box-sizing: border-box;
            transition: all 0.2s;
            line-height: 1.5;
        }
        .ai-img-textarea:focus {
            background: #ffffff;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
        }
        .ai-img-style-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }
        .ai-img-style-card {
            padding: 12px 6px;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            cursor: pointer;
            text-align: center;
            font-size: 12px;
            font-weight: 700;
            color: #334155;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            transition: all 0.15s ease;
        }
        .ai-img-style-card:hover {
            background: #ffffff;
            border-color: #cbd5e1;
            transform: translateY(-2px);
        }
        .ai-img-style-card.active {
            background: #ecfdf5;
            border-color: #10b981;
            color: #065f46;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }
        .ai-img-ratio-row {
            display: flex;
            gap: 8px;
        }
        .ai-img-ratio-btn {
            flex: 1;
            padding: 10px 6px;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            color: #475569;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            transition: all 0.15s ease;
        }
        .ai-img-ratio-btn:hover {
            background: #ffffff;
            border-color: #cbd5e1;
        }
        .ai-img-ratio-btn.active {
            background: #ecfdf5;
            border-color: #10b981;
            color: #065f46;
            box-shadow: 0 3px 10px rgba(16, 185, 129, 0.2);
        }
        .ai-img-generate-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: #ffffff;
            border: none;
            border-radius: 12px;
            font-size: 14.5px;
            font-weight: 800;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.35);
            transition: all 0.2s ease;
        }
        .ai-img-generate-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.45);
        }
        .ai-img-preview-box {
            flex: 1;
            min-height: 440px;
            background: #0f172a;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.25);
            border: 1.5px solid #1e293b;
        }
        .ai-img-preview-img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            border-radius: 14px;
            transition: opacity 0.3s ease;
        }
        .ai-img-overlay-toolbar {
            position: absolute;
            bottom: 16px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(15, 23, 42, 0.88);
            backdrop-filter: blur(12px);
            padding: 8px 16px;
            border-radius: 30px;
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: 0 8px 25px rgba(0,0,0,0.6);
            z-index: 10;
        }
        .ai-img-action-btn {
            padding: 6px 14px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            color: #ffffff;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s;
        }
        .ai-img-action-btn:hover {
            background: #10b981;
            border-color: #10b981;
        }
        .ai-img-gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
            gap: 12px;
            padding: 14px;
            background: #ffffff;
            border: 1.5px solid var(--ai-border);
            border-radius: 16px;
            max-height: 170px;
            overflow-y: auto;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
        }
        .ai-img-thumb-item {
            aspect-ratio: 1/1;
            border-radius: 12px;
            overflow: hidden;
            cursor: pointer;
            border: 2.5px solid transparent;
            position: relative;
            transition: all 0.15s ease;
        }
        .ai-img-thumb-item:hover {
            transform: scale(1.06);
            border-color: #10b981;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        .ai-img-thumb-item.active {
            border-color: #10b981;
            box-shadow: 0 0 0 2px #10b981;
        }
        .ai-img-thumb-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
EOD;

// Insert right before </style>
$aiPhp = str_replace('</style>', $imgCss . "\n    </style>", $aiPhp);

file_put_contents(__DIR__ . '/../student/ai.php', $aiPhp);
echo "Successfully injected AI Image Studio CSS into <style> block!\n";
