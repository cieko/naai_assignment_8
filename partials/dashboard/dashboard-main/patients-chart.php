<?php

/** @var mysqli $conn */

$ageData = getPatientsByAge($conn);
$gender = getPatientsByGender($conn);

$genderData = [
    [
        'label' => 'M',
        'name' => 'Male',
        'value' => (int)$gender['Male'],
        'color' => '#9fd8ff',
    ],
    [
        'label' => 'F',
        'name' => 'Female',
        'value' => (int)$gender['Female'],
        'color' => '#ffc9dc',
    ],
];

if ((int)$gender['Other'] > 0) {
    $genderData[] = [
        'label' => 'O',
        'name' => 'Other',
        'value' => (int)$gender['Other'],
        'color' => '#cfc7ff',
    ];
}

$jsonFlags = JSON_HEX_TAG
    | JSON_HEX_AMP
    | JSON_HEX_APOS
    | JSON_HEX_QUOT;

?>

<section class="card patients-insights">
    <article class="patients-insights-panel">
        <div
            id="patientsAgeChart"
            class="patients-insights-canvas">
        </div>

        <h4>By Age</h4>
    </article>

    <article class="patients-insights-panel patients-insights-panel--gender">
        <div
            id="patientsGenderChart"
            class="patients-insights-canvas patients-insights-canvas--gender">
        </div>

        <h4>By Gender</h4>
    </article>
</section>

<script>
(() => {
    const ageData = <?= json_encode($ageData, $jsonFlags) ?>;
    const genderData = <?= json_encode($genderData, $jsonFlags) ?>;

    const bootPatientCharts = () => {
        if (!window.d3) {
            return;
        }

        const d3 = window.d3;
        const ageTarget = document.getElementById('patientsAgeChart');
        const genderTarget = document.getElementById('patientsGenderChart');

        if (!ageTarget || !genderTarget) {
            return;
        }

        const drawEmptyState = (target, message) => {
            const empty = document.createElement('div');
            empty.className = 'patients-insights-empty';
            empty.textContent = message;
            target.appendChild(empty);
        };

        const renderAgeChart = () => {
            ageTarget.innerHTML = '';

            if (!ageData.length) {
                drawEmptyState(ageTarget, 'Add patients to view age distribution.');
                return;
            }

            const width = Math.max(ageTarget.clientWidth, 320);
            const height = 104;

            const svg = d3
                .select(ageTarget)
                .append('svg')
                .attr('class', 'patients-insights-svg')
                .attr('viewBox', `0 0 ${width} ${height}`)
                .attr('preserveAspectRatio', 'none');

            const bubbles = [...ageData]
                .sort((left, right) => right.value - left.value)
                .map((patient) => ({
                    ...patient,
                    r: Math.max(4, Math.min(22, 4 + (patient.age * 0.34))),
                }));

            const rowAnchors = [-6, 18, 44, 72, 98];
            const rowCursor = rowAnchors.map((_, index) => (
                index % 2 === 0 ? 0 : 18
            ));
            const layout = [];

            bubbles.forEach((bubble, index) => {
                let targetRow = 0;

                for (let i = 1; i < rowCursor.length; i += 1) {
                    if (rowCursor[i] < rowCursor[targetRow]) {
                        targetRow = i;
                    }
                }

                const x = rowCursor[targetRow] + bubble.r;
                const y = rowAnchors[targetRow] + ((index % 3) - 1) * 1.8;

                layout.push({
                    ...bubble,
                    x,
                    y,
                });

                rowCursor[targetRow] += (bubble.r * 2) + 6;
            });

            const maxX = d3.max(layout, (bubble) => bubble.x + bubble.r) || width;
            const scaleX = maxX > 0 ? width / maxX : 1;

            svg
                .append('g')
                .attr('transform', 'translate(0, 4)')
                .selectAll('circle')
                .data(layout)
                .join('circle')
                .attr('class', 'patients-age-bubble')
                .attr('cx', (d) => d.x * scaleX)
                .attr('cy', (d) => d.y)
                .attr('r', (d) => d.r)
                .attr('fill', (d) => d.color);
        };

        const renderGenderChart = () => {
            genderTarget.innerHTML = '';

            const activeGenderData = genderData.filter((item) => item.value > 0);

            if (!activeGenderData.length) {
                drawEmptyState(genderTarget, 'Add patients to view gender distribution.');
                return;
            }

            const width = Math.max(genderTarget.clientWidth, 220);
            const height = 104;
            const outerRadius = Math.min((width / 2) - 12, height - 8);
            const innerRadius = outerRadius * 0.72;
            const centerX = width / 2;
            const centerY = height + 8;

            const svg = d3
                .select(genderTarget)
                .append('svg')
                .attr('class', 'patients-insights-svg')
                .attr('viewBox', `0 0 ${width} ${height}`)
                .attr('preserveAspectRatio', 'none');

            const chart = svg
                .append('g')
                .attr('transform', `translate(${centerX}, ${centerY})`);

            const trackArc = d3.arc()
                .innerRadius(innerRadius)
                .outerRadius(outerRadius)
                .startAngle(-Math.PI / 2)
                .endAngle(Math.PI / 2);

            chart
                .append('path')
                .attr('class', 'patients-gender-track')
                .attr('d', trackArc());

            const pie = d3.pie()
                .value((d) => d.value)
                .sort(null)
                .startAngle(-Math.PI / 2)
                .endAngle(Math.PI / 2);

            const arc = d3.arc()
                .innerRadius(innerRadius)
                .outerRadius(outerRadius);

            chart
                .selectAll('path.patients-gender-arc')
                .data(pie(activeGenderData))
                .join('path')
                .attr('class', 'patients-gender-arc')
                .attr('fill', (d) => d.data.color)
                .attr('d', arc)
                .attr('opacity', 0.96);

            chart
                .selectAll('text.patients-gender-label')
                .data(pie(activeGenderData))
                .join('text')
                .attr('class', 'patients-gender-label')
                .attr('transform', (d) => {
                    const [x, y] = arc.centroid(d);
                    return `translate(${x}, ${y + 6})`;
                })
                .attr('text-anchor', 'middle')
                .text((d) => d.data.label);
        };

        const renderCharts = () => {
            renderAgeChart();
            renderGenderChart();
        };

        let resizeFrame = 0;

        window.addEventListener('resize', () => {
            window.cancelAnimationFrame(resizeFrame);
            resizeFrame = window.requestAnimationFrame(renderCharts);
        }, { passive: true });

        renderCharts();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootPatientCharts, { once: true });
    } else {
        bootPatientCharts();
    }
})();
</script>
