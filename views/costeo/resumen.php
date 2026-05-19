<?php if (!Session::puedeVerCostos()) Session::forbidden(); ?>

<div id="kpis-costeo" class="kpi-grid"></div>

<div class="card" style="margin-top:16px;">
    <div class="card-header">Top 10 productos por costo</div>
    <div class="form-section" id="grafica-costeo"></div>
</div>
