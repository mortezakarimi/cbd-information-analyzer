/**
 * This is admin js
 * @package           Cbd_Information_Analyzer
 */
(function ($) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */
	$(function () {
		if (typeof Chart === undefined) {
			return;
		}
		Chart.register(ChartDataLabels);
		const element = $('.target-actual-progress');
		for (const elementElement of element) {
			const ctx = elementElement.getContext('2d');
			const actual = $(elementElement).data('actual');
			const target = $(elementElement).data('target') === 0 ? 1 : $(elementElement).data('target');
			const remaining = target - actual;
			var chart = new Chart(ctx, {
				type: 'doughnut',
				data: {
					datasets: [{
						data: [actual, remaining],
						backgroundColor: [
							'rgb(54, 162, 235)',
							'rgb(255, 99, 132)'
						]
					}],
					labels: ['Progress', 'Remaining'],
					hoverOffset: 4
				},
				options: {
					cutoutPercentage: 70,
					tooltips: {
						enabled: false
					},
					legend: {
						display: false
					},
					plugins: {
						datalabels: {
							formatter: (value, ctx) => {

								let sum = 0;
								let dataArr = ctx.chart.data.datasets[0].data;
								dataArr.map(data => {
									sum += data;
								});
								let percentage = (value * 100 / sum).toFixed(2) + "%";
								return percentage;


							},
							color: '#fff',
						}
					}
				}
			});
		}
	})
})
(jQuery);
