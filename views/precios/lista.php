<?php if (!Session::puedeVerCostos()) Session::forbidden(); ?>

<div class="card">
    <div class="card-header">Lista de precios por tipo de cliente y moneda</div>
    <div class="form-section">
        <div class="form-row" style="display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:10px;">
            <div class="form-group"><label>Buscar</label><input id="pre-buscar" class="pwa-input" type="text" placeholder="Código o producto" oninput="filtrarPrecios()"></div>
            <div class="form-group"><label>Tipo producto</label><select id="pre-tipo" onchange="filtrarPrecios()"><option value="">Todos</option></select></div>
            <div class="form-group"><label>Tipo cliente</label><select id="pre-cliente" onchange="filtrarPrecios()"><option value="">Todos</option></select></div>
            <div class="form-group" style="align-self:end;"><button class="btn-secondary" type="button" onclick="exportarPrecios()">Exportar</button></div>
        </div>
        <div id="tabla-precios"></div>
    </div>
</div>
