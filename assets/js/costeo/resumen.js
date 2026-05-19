document.addEventListener('DOMContentLoaded', iniciarResumen);

async function iniciarResumen() {
    const res = await cwoFetch('/almacenes/api/costeo/resumen.php?action=kpis');
    if (!res.ok) return;

    const k = res.data.kpis || {};
    document.getElementById('kpis-costeo').innerHTML = `
        <div class="kpi-card"><div>Total productos costeados</div><strong>${Number(k.total_productos || 0)}</strong></div>
        <div class="kpi-card"><div>Costo promedio</div><strong>${Number(k.costo_promedio || 0).toFixed(2)}</strong></div>
        <div class="kpi-card"><div>Margen promedio</div><strong>${Number(k.margen_promedio || 0).toFixed(2)}%</strong></div>
        <div class="kpi-card"><div>Sin costear</div><strong>${Number(k.sin_costear || 0)}</strong></div>
    `;

    const top = res.data.top || [];
    const max = Math.max(...top.map(t => Number(t.costo_total || 0)), 1);
    document.getElementById('grafica-costeo').innerHTML = top.map(t => {
        const val = Number(t.costo_total || 0);
        return `
            <div style="margin-bottom:8px;">
                <div style="font-size:.85rem;">${t.codigo} · ${t.producto}</div>
                <div style="height:20px;background:#fff4ee;border-radius:8px;overflow:hidden;">
                    <div style="height:20px;width:${(val/max)*100}%;background:#ff6a00;"></div>
                </div>
            </div>
        `;
    }).join('');
}
