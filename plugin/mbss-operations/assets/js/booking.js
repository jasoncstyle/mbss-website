(function () {
	var root = document.getElementById("mbss-booking-app");
	var config = window.mbssBooking;
	if (!root || !config) {
		return;
	}

	var filters = [
		["all", "All trips"],
		["passage", "Passages"],
		["liveaboard", "Liveaboard"],
		["learn", "Learn to sail"],
		["advance", "ASA courses"],
		["online", "Online"]
	];

	var state = {
		view: "cards",
		filter: "all",
		month: firstOpenMonth(),
		course: null,
		session: null,
		occupancy: "private",
		addon: false,
		addon104: false,
		privateUpgrade: false,
		extraStudents: 0,
		quote: null,
		phase: "browse",
		submitted: null,
		student: {
			first_name: "",
			last_name: "",
			email: "",
			phone: "",
			address: "",
			emergency_contact: "",
			emergency_phone: "",
			asa_number: "",
			experience_level: "none",
			notes: ""
		},
		signatureName: "",
		accepted: false,
		signed: false,
		error: "",
		busy: false
	};

	function firstOpenMonth() {
		var earliest = null;
		(config.catalog || []).forEach(function (course) {
			(course.sessions || []).forEach(function (session) {
				if (session.open_enrollment || !session.start) {
					return;
				}
				var parts = session.start.split("-");
				var month = new Date(Number(parts[0]), Number(parts[1]) - 1, 1);
				if (!earliest || month < earliest) {
					earliest = month;
				}
			});
		});
		return earliest || startOfMonth(new Date());
	}

	function startOfMonth(date) {
		return new Date(date.getFullYear(), date.getMonth(), 1);
	}

	function money(value) {
		return Number(value || 0).toLocaleString("en-US", { style: "currency", currency: "USD" });
	}

	function visibleCourses() {
		return (config.catalog || []).filter(function (course) {
			if (state.filter !== "all" && course.path !== state.filter) {
				return false;
			}
			return (course.sessions || []).length > 0;
		});
	}

	function priceLabel(course) {
		if (course.pricing_model === "passage") {
			return "Private " + money(course.price) + (course.shared_price ? " / Shared " + money(course.shared_price) : "");
		}
		if (course.pricing_model === "package") {
			return money(course.price) + " per couple";
		}
		if (course.pay_in_full) {
			return money(course.price) + " paid in full";
		}
		return money(course.price) + " · " + money(course.deposit) + " deposit";
	}

	function seatLabel(course, session) {
		if (session.open_enrollment) {
			return "Open enrollment";
		}
		var noun = course.pricing_model === "passage" ? "cabin" : "seat";
		if (session.remaining <= 0) {
			return "Full";
		}
		if (session.remaining === 1) {
			return "1 " + noun + " left";
		}
		return session.remaining + " " + noun + "s open";
	}

	function selection() {
		return {
			session_id: state.session ? state.session.id : 0,
			occupancy: state.course && state.course.pricing_model === "passage" ? state.occupancy : "standard",
			private_upgrade: state.privateUpgrade,
			extra_students: state.extraStudents,
			addon: state.addon,
			addon_104: state.addon104
		};
	}

	function api(path, body) {
		return fetch(config.restUrl + path, {
			method: "POST",
			headers: {
				"Content-Type": "application/json",
				"X-WP-Nonce": config.nonce
			},
			body: JSON.stringify(body || {})
		}).then(function (response) {
			return response.json().then(function (data) {
				data = data || {};
				data._status = response.status;
				return data;
			});
		});
	}

	function el(tag, className, text) {
		var node = document.createElement(tag);
		if (className) {
			node.className = className;
		}
		if (text !== undefined) {
			node.textContent = text;
		}
		return node;
	}

	function button(label, className, onClick) {
		var node = el("button", className, label);
		node.type = "button";
		node.addEventListener("click", onClick);
		return node;
	}

	function loadQuote() {
		return api("quote", selection()).then(function (data) {
			state.quote = data.ok ? data : null;
			if (!data.ok) {
				state.error = data.error || "This trip could not be priced.";
			}
		});
	}

	function openCourse(course, session) {
		state.course = course;
		state.session = session || null;
		state.occupancy = "private";
		state.addon = false;
		state.addon104 = false;
		state.privateUpgrade = false;
		state.extraStudents = 0;
		state.quote = null;
		state.error = "";
		state.phase = "reserve";
		if (state.session && state.session.remaining > 0) {
			state.busy = true;
			render();
			loadQuote().finally(function () {
				state.busy = false;
				render();
			});
			return;
		}
		render();
	}

	function render() {
		root.innerHTML = "";
		if (state.phase === "submitted") {
			renderSubmitted();
			return;
		}
		if (state.phase === "reserve") {
			renderReserve();
			return;
		}
		renderBrowse();
	}

	function renderBrowse() {
		root.appendChild(el("p", "mbss-booking__kicker", "Calendar and book now"));
		root.appendChild(el("h2", "", "Upcoming training dates"));
		var how = el("ol", "mbss-how");
		[
			"Choose a date from the trip cards or the calendar.",
			"Open Reserve and send your reservation.",
			"Continue to secure Stripe checkout to confirm it, or leave the hold active until it expires."
		].forEach(function (line) {
			how.appendChild(el("li", "", line));
		});
		root.appendChild(how);

		var bar = el("div", "mbss-toolbar");
		filters.forEach(function (item) {
			var chip = button(item[1], "mbss-chip" + (state.filter === item[0] ? " is-active" : ""), function () {
				state.filter = item[0];
				render();
			});
			bar.appendChild(chip);
		});
		bar.appendChild(button("Trip cards", "mbss-chip" + (state.view === "cards" ? " is-active" : ""), function () {
			state.view = "cards";
			render();
		}));
		bar.appendChild(button("Calendar", "mbss-chip" + (state.view === "calendar" ? " is-active" : ""), function () {
			state.view = "calendar";
			render();
		}));
		root.appendChild(bar);

		if (state.view === "calendar") {
			root.appendChild(renderCalendar());
			root.appendChild(renderCards(sessionsInVisibleMonth()));
		} else {
			root.appendChild(renderCards(null));
		}
		root.appendChild(helpLine());
	}

	function renderCards(monthHits) {
		var grid = el("div", "mbss-choice-grid");
		var rows = [];
		if (monthHits) {
			rows = monthHits;
			if (!rows.length) {
				grid.appendChild(el("p", "", "No departures this month. Use Next to see later dates."));
				return grid;
			}
		} else {
			visibleCourses().forEach(function (course) {
				(course.sessions || []).forEach(function (session) {
					rows.push({ course: course, session: session });
				});
			});
			if (!rows.length) {
				grid.appendChild(el("p", "", "No published dates in this filter yet."));
				return grid;
			}
		}
		rows.forEach(function (row) {
			grid.appendChild(tripCard(row.course, row.session));
		});
		return grid;
	}

	function tripCard(course, session) {
		var card = el("article", "mbss-trip");
		card.appendChild(el("p", "mbss-trip__type", programName(course)));
		card.appendChild(el("h3", "", course.title));
		card.appendChild(el("p", "mbss-trip__date", session.label));
		card.appendChild(el("p", "", course.summary));
		card.appendChild(el("p", "mbss-trip__price", priceLabel(course)));
		card.appendChild(el("p", "mbss-choice__meta", course.duration + " · " + seatLabel(course, session)));
		var actions = el("div", "mbss-actions");
		actions.appendChild(button(session.remaining > 0 ? "Reserve" : "Join waitlist", "mbss-btn", function () {
			openCourse(course, session);
		}));
		card.appendChild(actions);
		return card;
	}

	function waiverCopy(course) {
		if (course.pricing_model === "passage") {
			return "The 10% deposit is non-refundable. The second payment is due 60 days before departure and the final balance is due 20 days before departure. You confirm you can swim and will follow the captain.";
		}
		if (course.pay_in_full) {
			return "This online course is paid in full today and the fee is non-refundable. The name on this reservation is the student who will take the course.";
		}
		return "The course deposit is non-refundable. The remaining balance is due 21 days before the start date. You confirm you can swim and will follow the captain.";
	}

	function programName(course) {
		var names = {
			passage: "Passage",
			liveaboard: "Liveaboard",
			learn: "Learn to sail",
			advance: "ASA course",
			online: "Online"
		};
		return names[course.path] || "Trip";
	}

	function renderCalendar() {
		var wrap = el("div", "mbss-calendar");
		var title = state.month.toLocaleDateString("en-US", { month: "long", year: "numeric" });
		var nav = el("div", "mbss-calendar__nav");
		nav.appendChild(button("Previous", "mbss-btn mbss-btn--ghost", function () {
			state.month = new Date(state.month.getFullYear(), state.month.getMonth() - 1, 1);
			render();
		}));
		nav.appendChild(el("strong", "", title));
		nav.appendChild(button("Next", "mbss-btn mbss-btn--ghost", function () {
			state.month = new Date(state.month.getFullYear(), state.month.getMonth() + 1, 1);
			render();
		}));
		wrap.appendChild(nav);
		var grid = el("div", "mbss-calendar__grid");
		["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"].forEach(function (day) {
			grid.appendChild(el("span", "mbss-calendar__dow", day));
		});
		var first = state.month.getDay();
		for (var blank = 0; blank < first; blank += 1) {
			grid.appendChild(el("span", "mbss-calendar__day is-empty"));
		}
		var days = new Date(state.month.getFullYear(), state.month.getMonth() + 1, 0).getDate();
		for (var day = 1; day <= days; day += 1) {
			var date = new Date(state.month.getFullYear(), state.month.getMonth(), day);
			var hits = sessionsOn(date);
			var cell = el("button", "mbss-calendar__day" + (hits.length ? " has-trip" : ""));
			cell.type = "button";
			cell.textContent = String(day);
			if (hits.length) {
				cell.title = hits.map(function (hit) { return hit.course.title; }).join(", ");
				cell.addEventListener("click", function (hits) {
					return function () {
						openCourse(hits[0].course, hits[0].session);
					};
				}(hits));
			} else {
				cell.disabled = true;
			}
			grid.appendChild(cell);
		}
		wrap.appendChild(grid);
		return wrap;
	}

	function sessionsInVisibleMonth() {
		var start = ymd(state.month);
		var end = ymd(new Date(state.month.getFullYear(), state.month.getMonth() + 1, 0));
		var hits = [];
		visibleCourses().forEach(function (course) {
			(course.sessions || []).forEach(function (session) {
				if (session.open_enrollment || !session.start || !session.end) {
					return;
				}
				if (session.end >= start && session.start <= end) {
					hits.push({ course: course, session: session });
				}
			});
		});
		hits.sort(function (a, b) {
			return a.session.start < b.session.start ? -1 : 1;
		});
		return hits;
	}

	function ymd(date) {
		return date.getFullYear() + "-" + String(date.getMonth() + 1).padStart(2, "0") + "-" + String(date.getDate()).padStart(2, "0");
	}

	function sessionsOn(date) {
		var key = date.getFullYear() + "-" + String(date.getMonth() + 1).padStart(2, "0") + "-" + String(date.getDate()).padStart(2, "0");
		var hits = [];
		visibleCourses().forEach(function (course) {
			(course.sessions || []).forEach(function (session) {
				if (session.open_enrollment) {
					return;
				}
				if (key >= session.start && key <= session.end) {
					hits.push({ course: course, session: session });
				}
			});
		});
		return hits;
	}

	function renderReserve() {
		var course = state.course;
		root.appendChild(button("Back to all trips", "mbss-btn mbss-btn--ghost", function () {
			state.phase = "browse";
			state.error = "";
			render();
		}));
		var panel = el("div", "mbss-booking__panel");
		panel.appendChild(el("p", "mbss-booking__kicker", programName(course)));
		panel.appendChild(el("h2", "", course.title));
		panel.appendChild(el("p", "", course.summary));
		if (state.error) {
			panel.appendChild(el("p", "mbss-alert", state.error));
		}

		if (!state.session || state.session.remaining <= 0) {
			if (state.session && state.session.remaining <= 0) {
				panel.appendChild(el("p", "", state.session.label + " is full."));
				panel.appendChild(waitlistForm());
			} else {
				panel.appendChild(el("h3", "", "Choose a date"));
				(course.sessions || []).forEach(function (session) {
					panel.appendChild(button(session.label + " · " + seatLabel(course, session), "mbss-choice", function () {
						openCourse(course, session);
					}));
				});
			}
			root.appendChild(panel);
			return;
		}

		panel.appendChild(el("p", "mbss-trip__date", state.session.label + " · " + seatLabel(course, state.session)));
		if (course.pricing_model === "passage") {
			panel.appendChild(occupancyField(course));
			if (course.allows_asa104) {
				panel.appendChild(check("Add ASA 104 Bareboat Cruising (" + money(course.asa104_price) + " per sailor)", state.addon104, function (checked) {
					state.addon104 = checked;
					refreshQuote(panel);
				}));
			}
			if (course.allows_asa106) {
				panel.appendChild(check("Add ASA 106 Advanced Coastal Cruising (" + money(course.asa106_price) + " per sailor)", state.addon, function (checked) {
					state.addon = checked;
					refreshQuote(panel);
				}));
			}
		} else if (course.pricing_model === "package") {
			panel.appendChild(numberField("Additional students at " + money(course.extra_student_price) + " each", state.extraStudents, function (value) {
				state.extraStudents = value;
				refreshQuote(panel);
			}));
		} else if (course.private_upgrade > 0) {
			panel.appendChild(check("Make this course private (+" + money(course.private_upgrade) + ")", state.privateUpgrade, function (checked) {
				state.privateUpgrade = checked;
				refreshQuote(panel);
			}));
		}

		if (state.quote) {
			panel.appendChild(quoteBlock());
		} else if (state.busy) {
			panel.appendChild(el("p", "", "Calculating the deposit…"));
		}

		panel.appendChild(el("h3", "", "Your reservation"));
		var form = el("div", "mbss-form mbss-form--grid");
		[
			["First name", "first_name", "text"],
			["Last name", "last_name", "text"],
			["Email", "email", "email"],
			["Phone", "phone", "tel"],
			["Emergency contact", "emergency_contact", "text"],
			["Emergency phone", "emergency_phone", "tel"]
		].forEach(function (spec) {
			form.appendChild(textField(spec[0], spec[1], spec[2]));
		});
		var notes = textField("Notes", "notes", "textarea");
		notes.classList.add("mbss-field--wide");
		form.appendChild(notes);
		panel.appendChild(form);

		panel.appendChild(el("h3", "", "Waiver"));
		panel.appendChild(el("p", "", waiverCopy(course)));
		panel.appendChild(textField("Legal name", "signatureName", "text", true));
		panel.appendChild(el("p", "", "Draw your signature"));
		var canvas = document.createElement("canvas");
		canvas.className = "mbss-signature";
		canvas.width = 640;
		canvas.height = 160;
		panel.appendChild(canvas);
		bindSignature(canvas);
		panel.appendChild(check("I agree to the refund, cancellation, and waiver terms.", state.accepted, function (checked) {
			state.accepted = checked;
		}));
		var reserve = button("Reserve this trip", "mbss-btn", function () { submitReservation(canvas); });
		reserve.disabled = state.busy;
		panel.appendChild(reserve);
		root.appendChild(panel);
		root.appendChild(helpLine());
	}

	function quoteBlock() {
		var box = el("dl", "mbss-summary");
		[
			["Trip price", money(state.quote.total)],
			["Deposit due to confirm", money(state.quote.deposit)],
			["Remaining balance", money(state.quote.balance)]
		].forEach(function (row) {
			var item = el("div");
			item.appendChild(el("dt", "", row[0]));
			item.appendChild(el("dd", "", row[1]));
			box.appendChild(item);
		});
		if (state.quote.schedule) {
			var list = el("ul", "mbss-schedule");
			state.quote.schedule.forEach(function (line) {
				var item = el("li");
				item.appendChild(el("span", "", line.label));
				item.appendChild(el("span", "", money(line.amount)));
				item.appendChild(el("span", "", line.payment_type === "deposit" ? "Due at checkout" : "Due " + line.due_on));
				list.appendChild(item);
			});
			box.appendChild(list);
		}
		return box;
	}

	function refreshQuote() {
		state.busy = true;
		loadQuote().finally(function () {
			state.busy = false;
			render();
		});
	}

	function textField(label, name, type, isSignature) {
		var wrap = el("label", "mbss-field");
		wrap.appendChild(document.createTextNode(label));
		var input = document.createElement(type === "textarea" ? "textarea" : "input");
		if (type !== "textarea") {
			input.type = type;
		}
		input.value = isSignature ? state.signatureName : (state.student[name] || "");
		input.addEventListener("input", function () {
			if (isSignature) {
				state.signatureName = input.value;
			} else {
				state.student[name] = input.value;
			}
		});
		wrap.appendChild(input);
		return wrap;
	}

	function occupancyField(course) {
		var set = el("fieldset", "mbss-fieldset");
		set.appendChild(el("legend", "", "Cabin"));
		[["private", "Private (" + money(course.price) + ")"], ["shared", "Shared (" + money(course.shared_price) + ")"]].forEach(function (choice) {
			if (choice[0] === "shared" && !course.shared_price) {
				return;
			}
			var label = el("label", "mbss-check");
			var input = document.createElement("input");
			input.type = "radio";
			input.name = "occupancy";
			input.checked = state.occupancy === choice[0];
			input.addEventListener("change", function () {
				state.occupancy = choice[0];
				refreshQuote();
			});
			label.appendChild(input);
			label.appendChild(document.createTextNode(choice[1]));
			set.appendChild(label);
		});
		return set;
	}

	function check(text, checked, onChange) {
		var label = el("label", "mbss-check");
		var input = document.createElement("input");
		input.type = "checkbox";
		input.checked = checked;
		input.addEventListener("change", function () { onChange(input.checked); });
		label.appendChild(input);
		label.appendChild(document.createTextNode(" " + text));
		return label;
	}

	function numberField(labelText, value, onChange) {
		var label = el("label", "mbss-field");
		label.appendChild(document.createTextNode(labelText));
		var input = document.createElement("input");
		input.type = "number";
		input.min = "0";
		input.max = "2";
		input.value = String(value);
		input.addEventListener("change", function () {
			onChange(Math.max(0, Math.min(2, parseInt(input.value || "0", 10))));
		});
		label.appendChild(input);
		return label;
	}

	function bindSignature(canvas) {
		state.signed = false;
		var ctx = canvas.getContext("2d");
		ctx.strokeStyle = "#1a2f4a";
		ctx.lineWidth = 2;
		var drawing = false;
		function point(event) {
			var rect = canvas.getBoundingClientRect();
			var source = event.touches ? event.touches[0] : event;
			return {
				x: (source.clientX - rect.left) * (canvas.width / rect.width),
				y: (source.clientY - rect.top) * (canvas.height / rect.height)
			};
		}
		function start(event) {
			drawing = true;
			var p = point(event);
			ctx.beginPath();
			ctx.moveTo(p.x, p.y);
			event.preventDefault();
		}
		function move(event) {
			if (!drawing) {
				return;
			}
			var p = point(event);
			ctx.lineTo(p.x, p.y);
			ctx.stroke();
			state.signed = true;
			event.preventDefault();
		}
		canvas.addEventListener("mousedown", start);
		canvas.addEventListener("mousemove", move);
		window.addEventListener("mouseup", function () { drawing = false; });
		canvas.addEventListener("touchstart", start, { passive: false });
		canvas.addEventListener("touchmove", move, { passive: false });
	}

	function submitReservation(canvas) {
		var phone = (state.student.phone || "").replace(/\D/g, "");
		if (!state.student.first_name || !state.student.last_name || !state.student.email || phone.length < 10) {
			state.error = "Enter your name, a valid email, and a phone number with at least 10 digits.";
			render();
			return;
		}
		if (state.signatureName.trim().length < 2 || !state.signed || !state.accepted) {
			state.error = "Type your name, draw a signature, and accept the terms.";
			render();
			return;
		}
		state.busy = true;
		state.error = "";
		render();
		var body = selection();
		body.student = state.student;
		body.signature_name = state.signatureName;
		body.signature_data = canvas.toDataURL("image/png");
		body.policy_accepted = true;
		api("bookings", body).then(function (result) {
			state.busy = false;
			if (!result.ok) {
				state.error = result.error || "The reservation could not be started.";
				state.phase = "reserve";
				render();
				return;
			}
			state.submitted = result;
			state.phase = "submitted";
			render();
		}).catch(function () {
			state.busy = false;
			state.error = "The booking service did not respond.";
			render();
		});
	}

	function renderSubmitted() {
		var result = state.submitted;
		var panel = el("section", "mbss-booking__panel");
		panel.appendChild(el("h2", "", "Reservation submitted"));
		panel.appendChild(el("p", "", "Next step: continue to secure checkout below to confirm this reservation."));
		panel.appendChild(el("p", "", "Booking reference: " + result.booking_ref));
		if (result.hold_until) {
			panel.appendChild(el("p", "", "Hold active until " + new Date(result.hold_until).toLocaleString() + "."));
		}
		panel.appendChild(el("p", "", "Deposit to confirm: " + money(result.deposit)));
		if (!config.stripeReady) {
			panel.appendChild(el("p", "mbss-alert", "Stripe is not connected in this environment. Continue will record a simulated deposit. No card is charged."));
		}
		panel.appendChild(button(config.stripeReady ? "Continue to secure checkout" : "Simulate deposit payment", "mbss-btn", function () {
			if (result.checkout_url) {
				window.location.href = result.checkout_url;
				return;
			}
			api("demo-confirm", { booking_ref: result.booking_ref }).then(function (confirmed) {
				if (!confirmed.ok) {
					state.error = confirmed.error || "The simulated payment failed.";
					render();
					return;
				}
				window.location.href = window.location.pathname + "?mbss_booking=" + encodeURIComponent(result.booking_ref);
			});
		}));
		panel.appendChild(button("Submit another reservation", "mbss-btn mbss-btn--ghost", function () {
			state.phase = "browse";
			state.submitted = null;
			render();
		}));
		if (state.error) {
			panel.insertBefore(el("p", "mbss-alert", state.error), panel.firstChild);
		}
		root.appendChild(panel);
	}

	function waitlistForm() {
		var box = el("form", "mbss-form");
		["first_name", "last_name", "email", "phone"].forEach(function (name) {
			var label = el("label", "mbss-field");
			label.appendChild(document.createTextNode(name.replace("_", " ")));
			var input = document.createElement("input");
			input.name = name;
			input.required = name !== "phone";
			input.type = name === "email" ? "email" : "text";
			label.appendChild(input);
			box.appendChild(label);
		});
		var submit = el("button", "mbss-btn", "Join waitlist");
		submit.type = "submit";
		box.appendChild(submit);
		box.addEventListener("submit", function (event) {
			event.preventDefault();
			var data = { session_id: state.session.id };
			["first_name", "last_name", "email", "phone"].forEach(function (name) {
				data[name] = box.querySelector("[name=" + name + "]").value;
			});
			api("waitlist", data).then(function (result) {
				if (result.ok) {
					box.replaceWith(el("p", "mbss-alert mbss-alert--ok", "You are on the waitlist. We email you if a cabin opens."));
				} else {
					state.error = result.error || "Could not join the waitlist.";
					render();
				}
			});
		});
		return box;
	}

	function helpLine() {
		var help = el("p", "mbss-booking__help");
		help.appendChild(document.createTextNode("Questions? Call "));
		var phone = el("a", "", "843-241-6874");
		phone.href = "tel:18432416874";
		help.appendChild(phone);
		help.appendChild(document.createTextNode(" or open "));
		var portal = el("a", "", "your booking");
		portal.href = config.portalUrl;
		help.appendChild(portal);
		help.appendChild(document.createTextNode(" to pay a later installment."));
		return help;
	}

	var pre = config.preselect || {};
	if (pre.course) {
		var match = (config.catalog || []).find(function (course) { return course.slug === pre.course; });
		if (match) {
			var session = pre.session ? (match.sessions || []).find(function (item) { return item.id === pre.session; }) : null;
			openCourse(match, session || null);
		} else {
			render();
		}
	} else {
		render();
	}
})();
