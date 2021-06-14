C:\xampp\php\php index.php welcome start_process
if %ERRORLEVEL% == 0 (
start /min branch_to_main_one.bat 
start /min branch_to_main_two.bat 
)
exit