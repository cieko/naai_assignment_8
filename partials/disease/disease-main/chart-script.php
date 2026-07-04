<?php
/** @var int $jsonFlags */
/** @var array<string, mixed> $trendPayload */
?>

<script>
(() => {
    const trendData = <?= json_encode($trendPayload, $jsonFlags) ?>;

    const bootDiseaseTrendChart = () => {
        if (!window.d3) {
            return;
        }

        const d3 = window.d3;
        const target = document.getElementById('diseaseTrendChart');

        if (!target) {
            return;
        }

        const drawEmptyState = (message) => {
            const empty = document.createElement('div');
            empty.className = 'disease-empty';
            empty.textContent = message;
            target.appendChild(empty);
        };

        const renderChart = () => {
            target.innerHTML = '';

            if (!trendData.series.length) {
                drawEmptyState('Add admissions with linked disease records to view the monthly trend.');
                return;
            }

            const width = Math.max(target.clientWidth, 320);
            const height = target.clientHeight || 320;
            const margin = { top: 18, right: 18, bottom: 42, left: 44 };
            const monthLabels = trendData.months.map((month) => month.label);
            const maxValue = Math.max(
                1,
                d3.max(
                    trendData.series,
                    (series) => d3.max(series.values, (point) => point.value)
                ) || 0
            );

            const svg = d3
                .select(target)
                .append('svg')
                .attr('class', 'disease-trend-svg')
                .attr('viewBox', `0 0 ${width} ${height}`)
                .attr('preserveAspectRatio', 'none');

            const x = d3
                .scalePoint()
                .domain(monthLabels)
                .range([margin.left, width - margin.right])
                .padding(0.35);

            const y = d3
                .scaleLinear()
                .domain([0, maxValue])
                .nice()
                .range([height - margin.bottom, margin.top]);

            svg
                .append('g')
                .attr('class', 'disease-trend-grid')
                .selectAll('line')
                .data(y.ticks(5))
                .join('line')
                .attr('x1', margin.left)
                .attr('x2', width - margin.right)
                .attr('y1', (tick) => y(tick))
                .attr('y2', (tick) => y(tick));

            svg
                .append('g')
                .attr('class', 'disease-trend-axis')
                .attr('transform', `translate(0, ${height - margin.bottom})`)
                .call(
                    d3.axisBottom(x)
                        .tickSize(0)
                );

            svg
                .append('g')
                .attr('class', 'disease-trend-axis')
                .attr('transform', `translate(${margin.left}, 0)`)
                .call(
                    d3.axisLeft(y)
                        .ticks(5)
                        .tickFormat(d3.format('d'))
                );

            const line = d3
                .line()
                .x((point, index) => x(monthLabels[index]))
                .y((point) => y(point.value))
                .curve(d3.curveMonotoneX);

            trendData.series.forEach((series) => {
                svg
                    .append('path')
                    .datum(series.values)
                    .attr('class', 'disease-trend-line')
                    .attr('stroke', series.color)
                    .attr('d', line);

                svg
                    .append('g')
                    .selectAll('circle')
                    .data(series.values)
                    .join('circle')
                    .attr('class', 'disease-trend-point')
                    .attr('cx', (point, index) => x(monthLabels[index]))
                    .attr('cy', (point) => y(point.value))
                    .attr('r', 4)
                    .attr('fill', series.color);
            });
        };

        let resizeFrame = 0;

        window.addEventListener('resize', () => {
            window.cancelAnimationFrame(resizeFrame);
            resizeFrame = window.requestAnimationFrame(renderChart);
        }, { passive: true });

        renderChart();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootDiseaseTrendChart, { once: true });
    } else {
        bootDiseaseTrendChart();
    }
})();
</script>
