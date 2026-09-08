export function buildCategoryPieOptions(itemsByCategories) {
    return {
        series: itemsByCategories.data,
        labels: itemsByCategories.label,
        legend: {
            position: "bottom",
            offsetX: 0,
            offsetY: 0,
            width: "100px",
        },
    };
}

export function buildCostPerCategoriesOptions(costPerCategories) {
    return {
        series: costPerCategories.data,
        labels: costPerCategories.label,
    };
}

export function buildMonthlyBarOptions(monthsLabel, seriesName, seriesData, valueSuffix, withBackground = false) {
    const dataLabels = withBackground
        ? {
              enabled: true,
              style: {},
              background: {
                  enabled: true,
                  foreColor: "#444",
                  borderRadius: 3,
                  padding: 4,
                  opacity: 0.5,
                  borderWidth: 2,
                  borderColor: "#fff",
              },
              formatter: (val) => val + valueSuffix,
          }
        : {
              enabled: true,
              formatter: (val) => val + valueSuffix,
          };

    return {
        options: {
            chart: {
                id: "basic-bar",
            },
            xaxis: {
                categories: monthsLabel,
            },
            dataLabels,
        },
        series: [
            {
                name: seriesName,
                data: seriesData,
            },
        ],
    };
}

export function buildStackedCostNbvOptions(nbvCostCategory) {
    return {
        series: nbvCostCategory,
        chart: {
            type: "bar",
            stacked: true,
            stackType: "100%",
        },
        responsive: [
            {
                breakpoint: 480,
                options: {
                    legend: {
                        position: "bottom",
                        offsetX: -10,
                        offsetY: 0,
                    },
                },
            },
        ],
        xaxis: {
            categories: ["COST", "NBV"],
            labels: {
                show: true,
                rotate: -45,
                rotateAlways: false,
                hideOverlappingLabels: true,
                showDuplicates: false,
                trim: false,
                minHeight: undefined,
                maxHeight: 120,
                style: {
                    colors: [],
                    fontSize: "13px",
                    fontWeight: "bold",
                    fontFamily: "Helvetica, Arial, sans-serif",
                    cssClass: "apexcharts-xaxis-label",
                },
            },
        },
        fill: {
            opacity: 1,
        },
        legend: {
            position: "bottom",
            offsetX: 0,
            offsetY: 0,
        },
    };
}

export function buildRadialSTOOptions(stoProgress) {
    return {
        series: [stoProgress],
        options: {
            chart: {
                height: 350,
                type: "radialBar",
                toolbar: {
                    show: false,
                },
            },
            plotOptions: {
                radialBar: {
                    startAngle: -135,
                    endAngle: 225,
                    hollow: {
                        margin: 0,
                        size: "65%",
                        background: "#fff",
                        image: undefined,
                        imageOffsetX: 0,
                        imageOffsetY: 0,
                        position: "front",
                        dropShadow: {
                            enabled: true,
                            top: 3,
                            left: 0,
                            blur: 4,
                            opacity: 0.5,
                        },
                    },
                    track: {
                        background: "#fff",
                        strokeWidth: "57%",
                        margin: 0,
                        dropShadow: {
                            enabled: true,
                            top: -3,
                            left: 0,
                            blur: 4,
                            opacity: 0.7,
                        },
                    },
                    dataLabels: {
                        show: true,
                        name: {
                            offsetY: -12,
                            show: true,
                            color: "#666",
                            fontSize: "14px",
                        },
                        value: {
                            formatter: (val) => parseInt(val),
                            offsetY: 8,
                            color: "#444",
                            fontSize: "40px",
                            fontWeight: "bold",
                            show: true,
                        },
                    },
                },
            },
            fill: {
                type: "gradient",
                gradient: {
                    shade: "dark",
                    type: "horizontal",
                    shadeIntensity: 0.5,
                    gradientToColors: ["#ABE5A1"],
                    inverseColors: true,
                    opacityFrom: 1,
                    opacityTo: 1,
                    stops: [0, 100],
                },
            },
            stroke: {
                lineCap: "round",
            },
            labels: ["STO (%)"],
        },
    };
}
