:: Run easy-coding-standard (ecs) via this batch file inside your IDE e.g. PhpStorm (Windows only)
:: Install inside PhpStorm the  "Batch Script Support" plugin
cd..
cd..
cd..
cd..
cd..
cd..
:: templates
vendor\bin\ecs check vendor/markocupic/sac-event-evaluation/src/Resources/contao/templates --fix --config vendor/markocupic/sac-event-evaluation/.ecs/config/template.php
::
cd vendor/markocupic/sac-event-evaluation/.ecs./batch/fix
