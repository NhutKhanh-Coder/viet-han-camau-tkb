<?php
$html = file_get_contents(__DIR__ . '/dashboard_rendered.html');
$doc = new DOMDocument();
@$doc->loadHTML($html);
$xpath = new DOMXPath($doc);
$grids = $xpath->query("//*[contains(@class, 'adm-dashboard-grid')]");

if ($grids->length > 0) {
    $grid = $grids->item(0);
    echo "Found adm-dashboard-grid! Number of children: " . $grid->childNodes->length . "\n";
    $childIdx = 0;
    foreach ($grid->childNodes as $child) {
        if ($child->nodeType === XML_ELEMENT_NODE) {
            $childIdx++;
            echo "Child $childIdx: <" . $child->nodeName . "> class='" . $child->getAttribute('class') . "' id='" . $child->getAttribute('id') . "'\n";
        }
    }
} else {
    echo "adm-dashboard-grid not found!\n";
}
