/*jslint browser: true, maxerr: 50, indent: 4 */
/*global PUNBB: true */

// INSTALL
PUNBB.pun_poll = (function () {
	"use strict";

	//
	function visible(el) {
		return (el && el.offsetWidth !== 0);
	}

	function get(el) {
		return document.getElementById(el);
	}

	//
	function switcher_link_onclick_handler() {
		var poll_block = get("pun_poll_form_block"),
			switcher_link = get("pun_poll_switcher_link"),
			el_status = get("pun_poll_block_status");

		//
		if (!poll_block || !switcher_link || !el_status) {
			return true;
		}

		if (!visible(poll_block)) {
			poll_block.style.display = "block";
			switcher_link.innerHTML = switcher_link.getAttribute("data-lang-hide");
			el_status.value = "1";
		} else {
			poll_block.style.display = "none";
			switcher_link.innerHTML = switcher_link.getAttribute("data-lang-show");
			el_status.value = "0";
		}

		return false;
	}

	//
	function el_add_options_onclick_handler() {
		var el_template = get("pun_poll_add_option_template"),
			new_poll_options_el = "",
			i,
			cl,
			max_fld_count = 0,
			max_item_count = 0,
			div_list,
			lbl,
			inp;

		if (!el_template) {
			return true;
		}

		new_poll_options_el = document.createElement("div");
		PUNBB.common.addClass(new_poll_options_el, "sf-set");
		new_poll_options_el.innerHTML = el_template.innerHTML;

		// Find all Poll options block
		div_list = document.getElementsByTagName("div");

		for (i = 0, cl = div_list.length; i < cl; i += 1) {
			var el = div_list[i];
			if (el.getAttribute('data-item-count') !== null && el.getAttribute('data-fld-count') !== null) {
				if (el.getAttribute('data-item-count') > max_item_count) {
					max_item_count = parseInt(el.getAttribute('data-item-count'), 10);
				}

				if (el.getAttribute('data-fld-count') > max_fld_count) {
					max_fld_count = parseInt(el.getAttribute('data-fld-count'), 10);
				}
			}
		}

		// Add class item and fld
		max_fld_count += 1;
		new_poll_options_el.setAttribute('data-fld-count', max_fld_count.toString());

		max_item_count += 1;
		PUNBB.common.addClass(new_poll_options_el, 'set' + max_item_count.toString());
		new_poll_options_el.setAttribute('data-item-count', max_item_count.toString());

		// Add fld for label and input
		lbl = new_poll_options_el.getElementsByTagName('label')[0];
		inp = new_poll_options_el.getElementsByTagName('input')[0];

		lbl.setAttribute('for', 'fld' + max_fld_count.toString());
		inp.setAttribute('id', 'fld' + max_fld_count.toString());

		el_template.parentNode.insertBefore(new_poll_options_el, el_template);

		inp.focus();
		return false;
	}


	//
	function vote_submit_button_handler_init() {
		var poll_list,
			fn;

		var fn_poll_item_filter = function (el) {
			return (PUNBB.common.hasClass(el, 'pun_poll_item') && PUNBB.common.hasClass(el, 'unvotted'));
		};

		// Find all Poll Items
		poll_list = PUNBB.common.arrayOfMatched(fn_poll_item_filter, document.getElementsByTagName('div'));
		PUNBB.common.map(vote_submit_button_handler, poll_list);
	}


	//
	function vote_submit_button_handler(poll_item) {
		var radio_btn,
			vote_btn,
			fn_validator;

		fn_validator = function (poll_item, submit_btn) {
			return function () {
				var checked_rb = PUNBB.common.arrayOfMatched(function (el) {
					return (el.type === 'radio' && el.name && el.name === 'answer' && el.checked);
				}, poll_item.getElementsByTagName('input'));

				if (checked_rb.length > 0) {
					vote_btn.removeAttribute('disabled');
				} else {
					vote_btn.setAttribute('disabled', 'disabled');
				}
			};
		};

		// Find vote button inside poll
		vote_btn = PUNBB.common.arrayOfMatched(function (el) {
			return (el.type === "submit" && el.name && el.name === "vote");
		}, poll_item.getElementsByTagName("input"))[0];

		// Find radio butons inside poll
		radio_btn = PUNBB.common.arrayOfMatched(function (el) {
			return (el.type === "radio" && el.name && el.name === "answer");
		}, poll_item.getElementsByTagName("input"));

		// Attach validator to radio buttons change
		if (vote_btn && radio_btn.length > 0) {
			PUNBB.common.map(function (x) {
				x.onchange = fn_validator(poll_item, vote_btn);
			}, radio_btn);

			// Run first time maualy for set status
			fn_validator(poll_item, vote_btn)();
		}
	}


	return {

		//
		init: function () {
			if (PUNBB.env.page === "post" || PUNBB.env.page === "postedit") {
				var switcher_link = get("pun_poll_switcher_link"),
					el_status = get("pun_poll_block_status"),
					el_add_options = get("pun_poll_add_options_link");

				if (switcher_link) {
					switcher_link.onclick = switcher_link_onclick_handler;
				}

				if (el_status) {
					el_status.value = visible(get("pun_poll_form_block")) ? "1" : "0";
				}

				if (el_add_options) {
					el_add_options.onclick = el_add_options_onclick_handler;
				}
			}

			if (PUNBB.env.page === "viewtopic") {
				vote_submit_button_handler_init();
			}
		}

	};
}());

// One onload handler
PUNBB.common.addDOMReadyEvent(PUNBB.pun_poll.init);
