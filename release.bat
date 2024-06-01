rmdir /S /Q mod_eventchart.zip
mkdir mod_eventchart.zip
robocopy /E /NDL /NJH /NJS /nc /ns /np tmpl     mod_eventchart.zip/tmpl
robocopy /E /NDL /NJH /NJS /nc /ns /np language mod_eventchart.zip/language
robocopy /E /NDL /NJH /NJS /nc /ns /np Helper   mod_eventchart.zip/Helper
copy LICENSE mod_eventchart.zip
copy *.html mod_eventchart.zip
copy *.php mod_eventchart.zip
copy *.xml mod_eventchart.zip
