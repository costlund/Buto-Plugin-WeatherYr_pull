# Buto-Plugin-WeatherYr_pull

Pull weather data from yr.no.

## Settings

```
plugin_modules:
  yr:
    plugin: weather/yr_pull
    settings:
      mysql: 'yml:/../buto_data/theme/[theme]/mysql.yml'
      url:
        - https://www.yr.no/sted/Sverige/Halland/Halmstad/varsel_time_for_time.xml
        - https://www.yr.no/place/Sweden/V%C3%A4sterbotten/Ume%C3%A5/varsel_time_for_time.xml
        - https://www.yr.no/sted/Sverige/Stockholm/Stockholm/varsel_time_for_time.xml
```

## Url
Schedule this url twice per hour.
```
http://localhost/yr/pull
```
