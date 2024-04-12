rmdir /S /Q mod_eventchart.zip
mkdir mod_eventchart.zip
robocopy /E /NDL /NJH /NJS /nc /ns /np tmpl mod_eventchart.zip/tmpl
copy LICENSE mod_eventchart.zip
copy *.html mod_eventchart.zip
copy *.php mod_eventchart.zip
copy *.xml mod_eventchart.zip
