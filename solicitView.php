<?php

$cachePath = __DIR__ . '/tmp';
$detailPath = __DIR__ . '/tmp/detail';
if(!file_exists($detailPath)) {
  mkdir($detailPath, 0777, true);
}
$targetPath = __DIR__ . '/solicitView_detail';
if(!file_exists($targetPath)) {
  mkdir($targetPath, 0777, true);
}
$fh = fopen(__DIR__ . '/solicitView.csv', 'w');
fputcsv($fh, array('ID', '組織', '組織網址', '活動名稱', '活動地區', '開始日期', '結束日期'));
for($i = 1; $i <= 8; $i++) {
  getPage($i);
}


function getPage($p = 1) {
  global $cachePath, $fh, $detailPath, $targetPath;
  $offset = ($p - 1) * 100;
  $pageUrl = 'http://sasw.mohw.gov.tw/app39/solicitView/index?max=100&offset=' . $offset;
  $cacheFile = $cachePath . '/page_' . $p;
  if(!file_exists($cacheFile)) {
    file_put_contents($cacheFile, file_get_contents($pageUrl));
  }
  $page = file_get_contents($cacheFile);
  $lines = explode('</tr>', $page);
  foreach($lines AS $line) {
    $cols = explode('</td>', $line);
    if(count($cols) != 7) continue;
    $url = '';
    $urlParts1 = explode('<a href="', $cols[0]);
    if(isset($urlParts1[1])) {
      $urlParts2 = explode('" target="_blank">', $urlParts1[1]);
      $url = $urlParts2[0];
    }
    $id = '';
    $urlParts1 = explode('/detail/', $cols[1]);
    if(isset($urlParts1[1])) {
      $urlParts2 = explode('">', $urlParts1[1]);
      $id = $urlParts2[0];
    }
    foreach($cols AS $k => $v) {
      $cols[$k] = trim(strip_tags($v));
    }
    $e = array(
      'id' => $id,
      'org' => $cols[0],
      'org_url' => $url,
      'name' => $cols[1],
      'target' => $cols[2],
      'date_begin' => $cols[3],
      'date_end' => $cols[4],
    );
    fputcsv($fh, $e);
    $detailFile = $detailPath . '/' . $id;
    if(!file_exists($detailFile)) {
      file_put_contents($detailFile, file_get_contents('http://sasw.mohw.gov.tw/app39/solicitView/detail/' . $id));
    }
    $detailPage = file_get_contents($detailFile);
    $detailPage = substr($detailPage, strpos($detailPage, '<table summary'));
    $detailLines = explode('</tr>', $detailPage);
    $data = array();
    foreach($detailLines AS $detailLine) {
      $detailCols = explode('</th>', $detailLine);
      if(count($detailCols) !== 2) {
        continue;
      }
      $pos = strpos($detailCols[0], '<tr');
      if(false !== $pos) {
        $detailCols[0] = substr($detailCols[0], $pos);
      }
      if(false !== strpos($detailCols[1], '下載閱讀')) {
        $pos = strpos($detailCols[1], '/app39/');
        $posEnd = strpos($detailCols[1], '"', $pos);
        $detailCols[1] = 'http://sasw.mohw.gov.tw' . substr($detailCols[1], $pos, $posEnd - $pos);
      }
      foreach($detailCols AS $k => $v) {
        $detailCols[$k] = trim(strip_tags($v));
      }
      $data[$detailCols[0]] = $detailCols[1];
    }
    file_put_contents($targetPath . '/' . $id . '.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
  }
}
