@once
    @push('styles')
    <link href="{{ asset('assets/vendor/flatpickr/flatpickr.min.css') }}" rel="stylesheet">
    <style>
        .date-range-picker-input[readonly] {
            background-color: #fff;
            cursor: pointer;
        }
        html[data-bs-theme="dark"] .date-range-picker-input[readonly],
        html.dark-theme .date-range-picker-input[readonly] {
            background-color: #1b2630;
            color: #e7eef5;
            border-color: #31424c;
        }
        html[data-bs-theme="dark"] .flatpickr-calendar,
        html.dark-theme .flatpickr-calendar {
            background: #1b2630;
            border-color: #31424c;
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.35);
        }
        html[data-bs-theme="dark"] .flatpickr-months .flatpickr-month,
        html.dark-theme .flatpickr-months .flatpickr-month,
        html[data-bs-theme="dark"] .flatpickr-current-month .flatpickr-monthDropdown-months,
        html.dark-theme .flatpickr-current-month .flatpickr-monthDropdown-months,
        html[data-bs-theme="dark"] .flatpickr-current-month input.cur-year,
        html.dark-theme .flatpickr-current-month input.cur-year,
        html[data-bs-theme="dark"] span.flatpickr-weekday,
        html.dark-theme span.flatpickr-weekday,
        html[data-bs-theme="dark"] .flatpickr-weekdays,
        html.dark-theme .flatpickr-weekdays {
            background: #1b2630;
            color: #e7eef5;
            fill: #e7eef5;
        }
        html[data-bs-theme="dark"] .flatpickr-months .flatpickr-prev-month,
        html.dark-theme .flatpickr-months .flatpickr-prev-month,
        html[data-bs-theme="dark"] .flatpickr-months .flatpickr-next-month,
        html.dark-theme .flatpickr-months .flatpickr-next-month {
            color: #e7eef5;
            fill: #e7eef5;
        }
        html[data-bs-theme="dark"] .flatpickr-day,
        html.dark-theme .flatpickr-day {
            color: #d6e0e8;
        }
        html[data-bs-theme="dark"] .flatpickr-day.prevMonthDay,
        html.dark-theme .flatpickr-day.prevMonthDay,
        html[data-bs-theme="dark"] .flatpickr-day.nextMonthDay,
        html.dark-theme .flatpickr-day.nextMonthDay {
            color: #6f8493;
        }
        html[data-bs-theme="dark"] .flatpickr-day:hover,
        html.dark-theme .flatpickr-day:hover {
            background: #243442;
            border-color: #243442;
        }
        html[data-bs-theme="dark"] .flatpickr-day.inRange,
        html.dark-theme .flatpickr-day.inRange {
            background: #304353;
            border-color: #304353;
            box-shadow: -5px 0 0 #304353, 5px 0 0 #304353;
        }
        html[data-bs-theme="dark"] .flatpickr-day.startRange,
        html.dark-theme .flatpickr-day.startRange,
        html[data-bs-theme="dark"] .flatpickr-day.endRange,
        html.dark-theme .flatpickr-day.endRange,
        html[data-bs-theme="dark"] .flatpickr-day.selected,
        html.dark-theme .flatpickr-day.selected {
            background: #1294a6;
            border-color: #1294a6;
            color: #fff;
        }
        html[data-bs-theme="dark"] .flatpickr-day.today,
        html.dark-theme .flatpickr-day.today {
            border-color: #4cc9db;
            color: #4cc9db;
        }
        html[data-bs-theme="dark"] .flatpickr-day.today:hover,
        html.dark-theme .flatpickr-day.today:hover {
            background: #243442;
            color: #4cc9db;
        }
    </style>
    @endpush

    @push('scripts')
    <script src="{{ asset('assets/vendor/flatpickr/flatpickr.min.js') }}"></script>
    <script>
        (function () {
            if (typeof flatpickr === 'undefined') {
                return;
            }

            const parseYmdDate = function (value) {
                if (!value) return null;
                const parts = value.split('-');
                if (parts.length !== 3) return null;

                const year = Number(parts[0]);
                const month = Number(parts[1]) - 1;
                const day = Number(parts[2]);

                if (!year || month < 0 || day < 1) return null;

                return new Date(year, month, day);
            };

            document.querySelectorAll('[data-date-range-picker]').forEach(function (dateRangeInput) {
                if (dateRangeInput.disabled) {
                    return;
                }

                const fromSelector = dateRangeInput.getAttribute('data-date-from');
                const toSelector = dateRangeInput.getAttribute('data-date-to');
                const dateFromInput = fromSelector ? document.querySelector(fromSelector) : null;
                const dateToInput = toSelector ? document.querySelector(toSelector) : null;

                if (!dateFromInput || !dateToInput) {
                    return;
                }

                const syncDateRangeDisplay = function (instance, selectedDates) {
                    if (!selectedDates.length) {
                        dateRangeInput.value = '';
                        return;
                    }

                    const startDate = selectedDates[0];
                    const endDate = selectedDates[1] || selectedDates[0];

                    dateFromInput.value = instance.formatDate(startDate, 'Y-m-d');
                    dateToInput.value = instance.formatDate(endDate, 'Y-m-d');
                    dateRangeInput.value = instance.formatDate(startDate, 'd-m-Y') + ' s/d ' + instance.formatDate(endDate, 'd-m-Y');
                };

                flatpickr(dateRangeInput, {
                    mode: 'range',
                    dateFormat: 'd-m-Y',
                    defaultDate: [parseYmdDate(dateFromInput.value), parseYmdDate(dateToInput.value)].filter(Boolean),
                    allowInput: false,
                    disableMobile: true,
                    onReady: function (selectedDates, dateStr, instance) {
                        syncDateRangeDisplay(instance, selectedDates);
                    },
                    onChange: function (selectedDates, dateStr, instance) {
                        if (!selectedDates.length) {
                            dateFromInput.value = '';
                            dateToInput.value = '';
                            dateRangeInput.value = '';
                            return;
                        }

                        syncDateRangeDisplay(instance, selectedDates);
                    }
                });
            });
        })();
    </script>
    @endpush
@endonce
