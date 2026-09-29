<?php
$file = 'templates/planning/index.html.twig';
$content = file_get_contents($file);

// 1. Hide the buttons
$content = preg_replace(
    '/(<div[^>]*>)\s*(<button[^>]*id="btn-valider"[^>]*>.*?<\/button>)\s*(<button[^>]*id="btn-publier"[^>]*>.*?<\/button>)\s*(<\/div>)/s',
    "$1\n            {% if is_granted('ROLE_PHARMACIEN') %}\n            $2\n            $3\n            {% endif %}\n            $4",
    $content
);

// 2. Hide draggable elements
$content = preg_replace(
    '/(<div class="col-md-2">)/s',
    "{% if is_granted('ROLE_PHARMACIEN') %}\n        $1",
    $content
);
$content = preg_replace(
    '/(<div class="col-md-10">)/s',
    "{% else %}\n        <div class=\"col-md-12\">\n        {% endif %}\n        $1",
    $content
);

// 3. Hide delete button in Modal
$content = preg_replace(
    '/(<button type="button" class="btn btn-outline-danger" id="btn-delete-event">.*?<\/button>)/s',
    "{% if is_granted('ROLE_PHARMACIEN') %}\n        $1\n        {% endif %}",
    $content
);

// 4. Disable draggable JS logic
$content = preg_replace(
    '/(var containerEl = document\.getElementById\(\'external-events\'\);.*?\}\);)/s',
    "{% if is_granted('ROLE_PHARMACIEN') %}\n    $1\n    {% endif %}",
    $content
);

// 5. Disable JS team list rendering
$content = preg_replace(
    '/(const teamUl = document\.getElementById\(\'team-list\'\);.*?\}\);)/s',
    "{% if is_granted('ROLE_PHARMACIEN') %}\n            $1\n            {% endif %}",
    $content
);

// 6. Disable editable/droppable in calendar config
$content = str_replace(
    "editable: true,",
    "editable: {{ is_granted('ROLE_PHARMACIEN') ? 'true' : 'false' }},",
    $content
);
$content = str_replace(
    "droppable: true,",
    "droppable: {{ is_granted('ROLE_PHARMACIEN') ? 'true' : 'false' }},",
    $content
);

file_put_contents($file, $content);
echo "Terminé.";
