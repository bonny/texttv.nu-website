<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TextTV Importresult</title>
</head>

<body>
    <h1>TextTV Importstatus</h1>

    <h2>Statusar senaste timmen</h2>

    <ul>
        @foreach ($statusCountsByMinutes as $minutes => $minuteStats)
            <li>
                {{ $minutes }} @choice('minut|minuter', $minutes)
                <ul>
                    @foreach ($minuteStats as $oneStat)
                        <li>
                            {{ $oneStat->import_result_count }}
                            {{ $oneStat->import_result }}
                        </li>
                    @endforeach
                </ul>
            </li>
        @endforeach
    </ul>

    <h2>Detaljerad status för senaste importerna</h2>

    <p>Status för {{ $latestPageImportsResults->count() }} senaste importerna:</p>

    <table border="1" cellpadding="10">
        <thead>
            <th>Datum</th>
            <th>Sidnummer</th>
            <th>Resultat</th>
        </thead>
        <tbody>
            @foreach ($latestPageImportsResults as $importResult)
                <tr>
                    <td>{{ $importResult->created_at }}</td>
                    <td>{{ $importResult->page_num }}</td>
                    <td>{{ $importResult->import_result }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>

</html>
