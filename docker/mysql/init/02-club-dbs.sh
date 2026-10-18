#!/bin/bash
# Le da permisos al usuario de la app sobre las bases de club (club_*): crearlas, usarlas y borrarlas.
# Las usan los tests (club_test_*) y, en local, el alta de clubes. Ver docs/arquitectura-multi-tenant.md.
#
# Igual que 01-testing-db.sh, lo ejecuta el entrypoint de mysql SOLO en el primer arranque
# (con el volumen vacío). Con un volumen anterior hay que recrearlo: ver docs/desarrollo-local.md.

# En GRANT, "_" es un comodín: se escapa para que el patrón sea literalmente "club_".
sql="GRANT ALL PRIVILEGES ON \`club\\_%\`.* TO '${MYSQL_USER}'@'%';"

if declare -F docker_process_sql > /dev/null; then
    # Con source: se usa el helper del entrypoint.
    docker_process_sql <<< "$sql"
else
    # Ejecutado como proceso aparte: se conecta como root por socket, igual que el helper.
    # MYSQL_PWD en lugar de -p: así la contraseña no aparece en la lista de procesos.
    MYSQL_PWD="${MYSQL_ROOT_PASSWORD}" mysql --protocol=socket -uroot <<< "$sql"
fi
