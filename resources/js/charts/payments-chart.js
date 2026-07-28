window.initPaymentsChart = function (elementId, options) {
    var el = document.getElementById(elementId);

    if (!el) {
        return;
    }

    import('apexcharts').then(function (module) {
        var ApexCharts = module.default;
        window.ApexCharts = ApexCharts;

        var chart = new ApexCharts(el, options);
        chart.render();
    });
};
