<?php

/** @var mysqli $conn */

$ageData = getPatientsByAge($conn);
$gender = getPatientsByGender($conn);

?>

<section class="patients-charts">

    <div class="card">

        <h3>Patients By Age</h3>

        <div id="ageChart"></div>

    </div>

    <div class="card">

        <h3>Patients By Gender</h3>

        <div id="genderChart"></div>

    </div>

</section>

<script>

const patients = <?= json_encode($ageData); ?>;

/* ---------------- AGE BUBBLES ---------------- */

const ageContainer = document.getElementById("ageChart");

const width = ageContainer.clientWidth;
const height = 250;

const svg = d3
    .select("#ageChart")
    .append("svg")
    .attr("width", width)
    .attr("height", height);

const tooltip = d3
    .select("body")
    .append("div")
    .attr("class","bubble-tooltip")
    .style("opacity",0);

const nodes = patients.map(patient => ({

    age: patient.age,

    color: patient.color,

    radius: 8 + (patient.age * 0.25)

}));

const simulation = d3.forceSimulation(nodes)

    .force("center", d3.forceCenter(width / 2, height / 2))

    .force("charge", d3.forceManyBody().strength(1))

    .force("collision",
        d3.forceCollide().radius(d => d.radius + 2)
    )

    .on("tick", ticked);

function ticked() {

    svg.selectAll("circle")

        .attr("cx", d => d.x = Math.max(d.radius, Math.min(width - d.radius, d.x)))

        .attr("cy", d => d.y = Math.max(d.radius, Math.min(height - d.radius, d.y)));

}


/* ---------------- GENDER ---------------- */

Highcharts.chart('genderChart',{

    chart:{
        type:'pie',
        backgroundColor:'transparent',
        height:250
    },

    title:null,

    credits:false,

    legend:{
        enabled:false
    },

    tooltip:{
        pointFormat:'<b>{point.y}</b> Patients'
    },

    plotOptions:{

        pie:{

            startAngle:-90,

            endAngle:90,

            center:['50%','90%'],

            size:'115%',

            innerSize:'72%',

            borderWidth:0,

            dataLabels:{
                enabled:false
            }

        }

    },

    series:[{

        data:[

            {

                name:'Male',

                y:<?= $gender['Male']?>,

                color:'#87C9F5'

            },

            {

                name:'Female',

                y:<?= $gender['Female']?>,

                color:'#F7C5D8'

            },

            {

                name:'Other',

                y:<?= $gender['Other']?>,

                color:'#9AECCF'

            }

        ]

    }]

});

</script>