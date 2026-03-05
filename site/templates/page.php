<?php
// templates/page.php — Generic static page
$page = getPage($pageData['route']);
if (!$page) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    return;
}
?>

<section class="page-header">
  <div class="container">
    <h1><?= e($page['title']) ?></h1>
  </div>
</section>

<section class="section-page">
  <div class="container">
    <div class="content-narrow">
      <div class="rich-text">
        <?= $page['content'] ?>
      </div>
    </div>
  </div>
</section>
