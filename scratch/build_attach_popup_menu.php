<?php
$aiPhp = file_get_contents(__DIR__ . '/../student/ai.php');

// 1. Add sleek Attach Popup Menu UI in Toolbar
$oldToolbar = '<div style="display:flex; align-items:center; gap:8px;">
                                <button type="button" class="ai-tool-btn" onclick="document.getElementById(\'fileInput\').click()" title="Đính kèm bất kỳ tệp tin (All files: Code, Ảnh, Văn bản, Data...)" style="background: #f1f5f9; border: 1px solid #e2e8f0; font-size: 12.5px; font-weight: 700; color: #0f172a; padding: 6px 12px; border-radius: 8px;">
                                    <i class="fa-solid fa-paperclip" style="color: #64748b;"></i>
                                    <span>Tệp tin</span>
                                </button>
                                <button type="button" class="ai-tool-btn" onclick="openNativeFolderPicker()" title="Mở toàn bộ thư mục dự án (Open Folder / Workspace)" style="background: #10b981; color: #ffffff; font-weight: 700; border: none; font-size: 12.5px; padding: 6px 14px; border-radius: 8px; box-shadow: 0 2px 8px rgba(16,185,129,0.25); cursor: pointer;">
                                    <i class="fa-solid fa-folder-open"></i>
                                    <span>Mở thư mục</span>
                                </button>
                            </div>';

$newToolbar = '<div style="display:flex; align-items:center; gap:8px; position:relative;" id="attachMenuWrapper">
                                <!-- Big Folder Button -->
                                <button type="button" class="ai-tool-btn" onclick="openNativeFolderPicker()" title="Mở toàn bộ thư mục dự án (Open Folder / Workspace)" style="background: #10b981; color: #ffffff; font-weight: 800; border: none; font-size: 13px; padding: 7px 16px; border-radius: 10px; box-shadow: 0 3px 10px rgba(16,185,129,0.3); cursor: pointer; display:flex; align-items:center; gap:6px;">
                                    <i class="fa-solid fa-folder-open" style="font-size:14px;"></i>
                                    <span>Chọn Thư Mục (Folder)</span>
                                </button>

                                <!-- Attach Files Button -->
                                <button type="button" class="ai-tool-btn" onclick="document.getElementById(\'fileInput\').click()" title="Đính kèm từng tệp tin lẻ (Files / Code / Images)" style="background: #f1f5f9; border: 1px solid #cbd5e1; font-size: 12.5px; font-weight: 700; color: #334155; padding: 7px 12px; border-radius: 10px; cursor: pointer; display:flex; align-items:center; gap:5px;">
                                    <i class="fa-solid fa-paperclip" style="color: #64748b;"></i>
                                    <span>Chọn Tệp (File)</span>
                                </button>
                            </div>';

$aiPhp = str_replace($oldToolbar, $newToolbar, $aiPhp);

file_put_contents(__DIR__ . '/../student/ai.php', $aiPhp);
echo "Successfully updated attach buttons with crystal clear labels in student/ai.php!\n";
