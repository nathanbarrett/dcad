<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    <style>
        .pointer {
            cursor: pointer;
        }
    </style>
    @vite('resources/js/app.ts')
    @inertiaHead
</head>
<body>
@inertia
</body>
<script
    src="https://maps.googleapis.com/maps/api/js?key={!! config()->get('services.google.maps.api_key') !!}&callback=initMap&v=weekly&libraries=drawing"
    defer
></script>
<script type="text/javascript">
    function initMap() {
        window.googleMapsLoaded = true;
    }
</script>
</html>
