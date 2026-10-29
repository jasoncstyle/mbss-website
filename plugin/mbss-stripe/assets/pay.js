(function () {
	var select = document.getElementById("mbss-stripe-course");
	var date = document.getElementById("mbss-stripe-date");
	var summary = document.getElementById("mbss-stripe-summary");
	var due = document.getElementById("mbss-stripe-due");
	var later = document.getElementById("mbss-stripe-later");
	var dateLabel = document.getElementById("mbss-stripe-date-label");
	if (!select || !date || !summary || !due || !later) {
		return;
	}

	var days = (window.mbssStripe && window.mbssStripe.balanceDays) || 14;

	function money(cents) {
		return "$" + (cents / 100).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
	}

	function dueDate(value) {
		if (!value) {
			return "";
		}
		var parts = value.split("-");
		var lesson = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
		lesson.setDate(lesson.getDate() - days);
		var today = new Date();
		today.setHours(0, 0, 0, 0);
		if (lesson < today) {
			lesson = today;
		}
		var month = String(lesson.getMonth() + 1).padStart(2, "0");
		var day = String(lesson.getDate()).padStart(2, "0");
		return lesson.getFullYear() + "-" + month + "-" + day;
	}

	function refresh() {
		var option = select.options[select.selectedIndex];
		if (!option || !option.value) {
			summary.hidden = true;
			date.required = true;
			dateLabel.hidden = false;
			return;
		}
		var price = Number(option.getAttribute("data-price") || "0");
		var deposit = Number(option.getAttribute("data-deposit") || "0");
		var online = option.getAttribute("data-online") === "1";
		summary.hidden = false;
		dateLabel.hidden = online;
		date.required = !online;
		if (online) {
			due.textContent = "Stripe charges " + money(price) + " today.";
			later.textContent = "This online course is paid in full.";
			return;
		}
		var charge = Math.min(deposit, price);
		due.textContent = "Stripe charges the " + money(charge) + " deposit today.";
		var balance = price - charge;
		if (balance <= 0) {
			later.textContent = "Nothing is due after the deposit.";
			return;
		}
		var when = date.value ? dueDate(date.value) : "";
		later.textContent = "Balance of " + money(balance) + (when ? " is due " + when + "." : " is due " + days + " days before the lesson.");
	}

	select.addEventListener("change", refresh);
	date.addEventListener("change", refresh);
	refresh();
})();
