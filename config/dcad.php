<?php

return  [

    'download_url' => env('DCAD_DOWNLOAD_URL', 'https://www.dallascad.org/ViewPDFs.aspx?type=3&id=\\\\DCAD.ORG\\WEB\\WEBDATA\\WEBFORMS\\DATA%20PRODUCTS\\DCAD2023_CURRENT.ZIP'),

    'archive_retention_days' => (int) env('DCAD_ARCHIVE_RETENTION_DAYS', 90),

];
