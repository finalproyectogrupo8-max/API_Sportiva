#!/bin/bash
# Crea la base de testing y le da permisos al usuario de la app.
#
# Lo ejecuta el entrypoint de mysql SOLO en el primer arranque (con el volumen vacío).
# El entrypoint hace "source" de los .sh que no son ejecutables y ejecuta los que sí lo son.
# Como el bit de ejecución depende del sistema de archivos del host (NTFS o ext4),
# este script funciona de las dos formas.

testing_db="${MYSQL_TESTING_DATABASE:-sportiva_testing}"
# En GRANT, "_" es un comodín: se escapa para que el permiso aplique solo a esta base.
testing_db_grant="${testing_db//_/\\_}"

sql="CREATE DATABASE IF NOT EXISTS \`${testing_db}\`;
GRANT ALL PRIVILEGES ON \`${testing_db_grant}\`.* TO '${MYSQL_USER}'@'%';"

if declare -F docker_process_sql > /dev/null; then
    # Con source: se usa el helper del entrypoint.
    docker_process_sql <<< "$sql"
else
    # Ejecutado como proceso aparte: se conecta como root por socket, igual que el helper.
    # MYSQL_PWD en lugar de -p: así la contraseña no aparece en la lista de procesos.
    MYSQL_PWD="${MYSQL_ROOT_PASSWORD}" mysql --protocol=socket -uroot <<< "$sql"
fi
