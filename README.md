
# Static HTML Employee Profile Website

A single-page HTML website that lets you:
- Select **Employee ID** and view profile
- **Clock In/Clock Out** with automatic hour calculation (overnight shifts handled)
- See **All-time** hours and **Date-range filtered** hours
- View **Recent attendance**
- **Import** data from Excel (`.xlsx`) or CSV (`.csv`)
- Store data in **LocalStorage** (no backend needed)

## How to Run
1. Unzip the archive
2. Open `index.html` in your browser (double-click)

> If you plan to import **Excel (.xlsx)**, keep your computer online so the embedded SheetJS CDN can load. Otherwise, use **CSV** import.

## Excel Format
- **Employees** sheet columns: `EmployeeID`, `Name`, `Department`, `Role`, `JoinDate`, `Status`
- **Attendance** sheet columns: `EmployeeID`, `Date (yyyy-mm-dd)`, `ClockIn (hh:mm)`, `ClockOut (hh:mm)`

## CSV Format
- Employees CSV: headers must include at least `EmployeeID,Name`
- Attendance CSV: `EmployeeID,Date,ClockIn,ClockOut`

## Notes
- Data persists in `localStorage` until you clear browser storage.
- Hours = `(ClockOut - ClockIn)` in hours; if `ClockOut < ClockIn`, it's treated as **overnight** shift.
- Timezone: your device's local time is used for clock-in/out.
