<?php
$file = 'templates/user/_form.html.twig';
$content = file_get_contents($file);

$newSection = <<<HTML
    <!-- Paramètres Planning -->
    <div class="col-md-12 mt-4">
        <h5 class="mb-3 text-dark fw-bold border-bottom pb-2">
            <i class="bi bi-calendar-week text-primary me-2"></i> Paramètres Planning & RH
        </h5>
    </div>

    <div class="col-md-4">
        <div class="form-floating">
            {{ form_widget(form.qualification, {'attr': {'class': 'form-select'}}) }}
            <label for="{{ form.qualification.vars.id }}">Qualification</label>
            {{ form_errors(form.qualification) }}
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="form-floating">
            {{ form_widget(form.tempsTravailHebdo, {'attr': {'class': 'form-control', 'placeholder': '35'}}) }}
            <label for="{{ form.tempsTravailHebdo.vars.id }}">Heures/semaine</label>
            {{ form_errors(form.tempsTravailHebdo) }}
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="form-floating">
            {{ form_widget(form.numeroRpps, {'attr': {'class': 'form-control', 'placeholder': 'RPPS'}}) }}
            <label for="{{ form.numeroRpps.vars.id }}">Numéro RPPS (Pharmaciens)</label>
            {{ form_errors(form.numeroRpps) }}
        </div>
    </div>
</div>

<div class="mt-4 pt-3 border-top text-end">
HTML;

$content = str_replace(
    '</div>

<div class="mt-5 pt-3 border-top text-end">',
    $newSection,
    $content
);

file_put_contents($file, $content);
echo "Modification terminée.";
