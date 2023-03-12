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
		const element = $('.target-actual-progress');
		for (const elementElement of element) {
			const ctx = elementElement.getContext('2d');
			const actual = $(elementElement).data('actual') === 0 ? 1 : $(elementElement).data('actual');
			const target = $(elementElement).data('target') === 0 ? actual : $(elementElement).data('target');
			const percent = ((actual / target) * 100);
			const data = {
				labels: [
					'Actual',
					'Target',
				],
				datasets: [{
					data: [percent, 100 - percent],
					backgroundColor: [
						'rgb(54, 162, 235)',
						'rgb(255, 99, 132)'
					],
					hoverOffset: 4
				}]
			};
			var chart = new Chart(ctx, {
				type: 'pie',
				data: data
			})
		}
	})
})
(jQuery);
