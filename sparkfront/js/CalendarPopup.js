function CalendarPopup(elm) {
    //check elm

    var field = $(elm).parents(".InputField").first();
    var field_name = field.attr("field");
    this.field_name = field_name;
    this.field = field;


    if (this.closeCalendar()) {
        return;
    }


    var day = field.find(".DatePart.Day").val();
    var month = field.find(".DatePart.Month").val();
    var year = field.find(".DatePart.Year").val();

    var date_now = new Date();

    if (!day || (day < 0 || day > 31)) day = date_now.getDate();
    if (!month || (month < 0 || month > 12)) month = date_now.getMonth() + 1;
    if (!year || (year < 1)) year = date_now.getFullYear();

    var sel_date = new Date(year, month - 1, day, 7, 0, 0, 0);


    var popup = $("<div class='PopupPanel CalendarPopup'></div>");
    var pos = $(elm).offset();
    var width = $(elm).outerWidth(true);
    var x = pos.left + width;
    var y = pos.top;

    popup.css("position", "absolute");
    popup.css("left", x);
    popup.css("top", y);
    popup.css("display", "inline-block");

    popup.attr("field", field_name);
    popup.attr("date", sel_date.getTime());


    var html = this.calendarForDate(sel_date);

    popup.html(html);
    $('body').append(popup);

    this.setupButtons();
}

CalendarPopup.prototype.closeCalendar = function () {

    var current_popup = $("body").find(".CalendarPopup[field='" + this.field_name + "']");
    if (current_popup.get(0)) {

        current_popup.remove();
        this.field_name = null;
        this.field = null;

        return true;
    }
    return false;
}

CalendarPopup.prototype.setupButtons = function () {
    var field_name = this.field_name; //popup.attr("field");


    var current_calendar = $(".CalendarPopup[field='" + field_name + "']");
    var microsec = current_calendar.attr("date");
    var current_date = new Date(parseInt(microsec));


    var prev_month = current_calendar.find(".Button.Prev");
    prev_month.css("cursor", "pointer");

    prev_month.click(function (event) {

        current_date.setMonth(current_date.getMonth() - 1);

        var prev_html = this.calendarForDate(current_date);

        current_calendar.html(prev_html);
        current_calendar.attr("date", current_date.getTime());
        this.setupButtons();

    }.bind(this));

    var next_month = current_calendar.find(".Button.Next");
    next_month.css("cursor", "pointer");
    next_month.click(function (event) {

        current_date.setMonth(current_date.getMonth() + 1);

        var next_html = this.calendarForDate(current_date);

        current_calendar.html(next_html);
        current_calendar.attr("date", current_date.getTime());
        this.setupButtons();

    }.bind(this));


    var calendar = this;

    current_calendar.find(".Day").click(function (event) {

        var selected_date = new Date();
        selected_date.setTime(current_date.getTime());

        var day = $(this).html();
        day = parseInt(day);


        if ($(this).hasClass("PrevMonth")) {
            selected_date.setMonth(current_date.getMonth() - 1);
            selected_date.setDate(day);
        } else if ($(this).hasClass("NextMonth")) {
            selected_date.setMonth(current_date.getMonth() + 1);
            selected_date.setDate(day);
        } else {
            selected_date.setDate(day);
        }

        if ($(this).hasClass("NextYear")) {
            selected_date.setFullYear(current_date.getFullYear() + 1);
            selected_date.setMonth(0);
        } else if ($(this).hasClass("PrevYear")) {
            selected_date.setFullYear(current_date.getFullYear() - 1);
            selected_date.setMonth(11);
        }

        calendar.updateField(selected_date);

    });

}
CalendarPopup.prototype.updateField = function (selected_date) {

    var field = this.field;
    console.log(selected_date);

    var day = selected_date.getDate();
    var month = selected_date.getMonth() + 1;
    var year = selected_date.getFullYear();

    console.log("Day: " + day + " | Month: " + month + " | Year: " + year);

    field.find(".DatePart.Day").val(day);
    field.find(".DatePart.Month").val(month);
    field.find(".DatePart.Year").val(year);

    this.closeCalendar();

}

CalendarPopup.prototype.dayNames = function () {
    var weekday = new Array("Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday");
    return weekday;


}

CalendarPopup.prototype.monthNames = function () {
    var months = new Array("January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
    return months;
}

CalendarPopup.prototype.calendarForDate = function (sel_date) {
    var date_now = new Date();

    var weekday = this.dayNames();
    var months = this.monthNames();

    var html = "<table id=calendar border=0 cellpadding=0 cellspacing=0>";

    html += "<tr>";
    html += "<td align=left colspan=1 >";
    html += "<div class='Button Prev'> < </div>";
    html += "</td>";

    html += "<td align=center colspan=5 class='MonthYearCaption'>" + months[sel_date.getMonth()] + " " + sel_date.getFullYear() + "</td>";

    html += "<td align=right colspan=1>";
    html += "<a class='Button Next'> > </a>";
    html += "</td>";
    html += "</tr>";

    html += "<tr>";

    for (var a = 0; a < weekday.length; a++) {
        var cday = weekday[a];
        html += "<td class='WeekdayName' >" + cday.substring(0, 3) + "</td>";
    }
    html += "</tr>";


    var total_days = daysInMonth(sel_date.getMonth(), sel_date.getYear());
    var start_date = new Date(sel_date.getFullYear(), sel_date.getMonth(), 1, 7, 0, 0, 0);


    start_date.setDate(1 - start_date.getDay());


    var finish_date = new Date(sel_date.getFullYear(), sel_date.getMonth(), total_days, 7, 0, 0, 0);

    finish_date.setDate(finish_date.getDate() + (6 - finish_date.getDay()));

    while (start_date.getTime() <= finish_date.getTime()) {
        if (start_date.getDay() == 0) html += "<tr>";
        var cls = "Day";


        if (start_date.getMonth() < sel_date.getMonth()) {

            if (start_date.getYear() > sel_date.getYear()) {
                cls += " NextMonth NextYear";
            } else {
                cls += " PrevMonth";
            }
        } else if (start_date.getMonth() > sel_date.getMonth()) {

            if (start_date.getYear() < sel_date.getYear()) {
                cls += " PrevMonth PrevYear";
            } else {
                cls += " NextMonth";
            }
        } else if (start_date.getTime() == sel_date.getTime()) {
            cls += " Selected";
        }

        if (start_date.getTime() == date_now.getTime()) {
            cls += " Current";
        }


        html += "<td class='" + cls + "' align=center >";
        html += start_date.getDate();
        html += "</td>";
        if (start_date.getDay() == 6) html += "</tr>";
        start_date.setDate(start_date.getDate() + 1);

    }

    html += "</table>";
    return html;
}


function daysInMonth(iMonth, iYear) {
    return 32 - new Date(iYear, iMonth, 32).getDate();
}




