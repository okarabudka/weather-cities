<?php
/**
 * @var array{lat: string, lon: string} $form
 * @var list<string> $errors
 * @var list<\WeatherCities\Domain\CityWeather> $rows
 * @var bool $withDistance
 * @var \Closure(string|int|float|null): string $e
 */
?>
<h1>Weather by Cities</h1>

<form method="get" action="/">
    <div class="form-row">
        <div>
            <label for="lat">Latitude</label>
            <input type="text" inputmode="decimal" id="lat" name="lat" value="<?= $e($form['lat']) ?>" placeholder="52.5200">
        </div>
        <div>
            <label for="lon">Longitude</label>
            <input type="text" inputmode="decimal" id="lon" name="lon" value="<?= $e($form['lon']) ?>" placeholder="13.4050">
        </div>
        <div>
            <button type="submit">Show distance</button>
        </div>
    </div>
</form>

<?php if ($errors !== []): ?>
    <div class="errors" role="alert">
        <?php foreach ($errors as $error): ?>
            <div><?= $e($error) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($rows !== []): ?>
<p><?= count($rows) ?> cities, sorted by temperature spread (max − min), smallest first.</p>
<div class="table-wrap">
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>City</th>
            <th>Min °C</th>
            <th>Max °C</th>
            <th>Spread °C</th>
            <th>Distance (km)</th>
            <th>Weather</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $i => $row): ?>
        <?php $weather = $row->weather; ?>
        <tr<?= $weather === null ? ' class="missing"' : '' ?>>
            <td><?= $i + 1 ?></td>
            <td><?= $e($row->city->name) ?> <span class="zip"><?= $e($row->city->zip) ?></span></td>
            <?php if ($weather === null): ?>
                <td colspan="3">no data</td>
            <?php else: ?>
                <td class="num"><?= number_format($weather->tempMin, 1) ?></td>
                <td class="num"><?= number_format($weather->tempMax, 1) ?></td>
                <td class="num"><?= number_format($weather->spread(), 1) ?></td>
            <?php endif; ?>
            <td class="num"><?= $withDistance && $row->distanceKm !== null ? number_format($row->distanceKm, 1) : '—' ?></td>
            <td>
                <?php if ($weather !== null): ?>
                    <img class="icon" src="https://openweathermap.org/img/wn/<?= rawurlencode($weather->icon) ?>@2x.png" alt="">
                    <?= $e($weather->description) ?>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>
