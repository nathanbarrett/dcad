@extends('layouts.vuetify')

@section('content')
    <v-container>
        <v-combobox
            v-model="zipCodes"
            label="Zip Codes"
            multiple
            chips
            deletable-chips
            clearable
            hide-details
            @change="fetchChanges"></v-combobox>
        <v-text-field v-model="days" label="Days Back" type="number" @change="fetchChanges"></v-text-field>
        <div id="dailyCounts"></div>
    </v-container>

@endsection

@push('scripts')
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script>
        google.charts.load("current", {packages:['corechart']});
        const app = new Vue({
            el: '#app',
            vuetify: new Vuetify(),
            data() {
                return {
                    dailyCounts: {!! json_encode($dailyCounts) !!},
                    days: {!! $days !!},
                    zipCodes: {!! json_encode($zipCodes) !!},
                    chart: null,
                }
            },
            methods: {
                drawChart() {
                    let start = dayjs().subtract(this.days, 'days').startOf('day');
                    const chartData = [['Date', 'Ownership Changes', 'Percentage Updates']];
                    while (start.isBefore(dayjs())) {
                        const date = start.format('YYYY-MM-DD');
                        const dailyCount = this.dailyCounts[date] || { ownership_updates: 0, percentage_updates: 0 };
                        chartData.push([date, parseInt(dailyCount.ownership_updates), parseInt(dailyCount.percentage_updates)]);
                        start = start.add(1, 'day');
                    }
                    const data = google.visualization.arrayToDataTable(chartData);
                    if (!this.chart) {
                        this.chart = new google.visualization.ColumnChart(document.getElementById('dailyCounts'));
                    }
                    this.chart.draw(data, {
                        title: 'Daily Updates for ' + this.zipCodes.length > 0 ? this.zipCodes.join(', ') : 'All Zip Codes',
                        width: 900,
                        height: 500,
                        legend: { position: 'top', maxLines: 3 },
                        bar: { groupWidth: '75%' },
                        isStacked: true,
                    });
                },
                async fetchChanges() {
                    const results = await axios.get('/metrics/daily-changes-detected', {
                        params: {
                            zip_codes: this.zipCodes,
                            days: parseInt(this.days),
                        }
                    });
                    this.dailyCounts = results.data.dailyCounts;
                    this.drawChart();
                }
            }
        });
        google.charts.setOnLoadCallback(app.drawChart);
    </script>
@endpush
