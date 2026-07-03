document.addEventListener("DOMContentLoaded", () => {

    const { Calendar } = window.VanillaCalendarPro;

    const calendar = new Calendar("#calendar", {
        locale: "en",
        firstWeekday: 1, // Monday
        enableWeekNumbers: false,
        selectedTheme: "light"
    });

    calendar.init();

});