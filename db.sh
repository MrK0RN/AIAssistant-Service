echo "Создаем PostgreSQL пользователя и БД"
# Create user and database, then grant privileges
sudo -i -u postgres psql << EOF
CREATE USER openai_assistants_db_2 WITH PASSWORD 'cu3ndh3behc';
CREATE DATABASE openai_assistants_db_2;
GRANT ALL PRIVILEGES ON DATABASE openai_assistants_db_2 TO openai_assistants_db_2;
\q
EOF
echo "PostgreSQL пользователь 'openai_assistants_db' и БД'openai_assistants_db' Созданы и все привелегии даны."

echo "PostgreSQL полностью работоспособна!"
echo "Название БД: openai_assistants_db"
echo "Username: openai_assistants_db"
echo "Password: cu3ndh3behc"

echo "Запускаем скрипт для создания структуры PostgreSQL"
PGPASSWORD=cu3ndh3behc psql -h localhost -p 5432 -U openai_assistants_db_2 -d openai_assistants_db_2 -f Assist/db_setup.sql

echo "Полностью развернули PostgreSQL, разворачиваем Docker и Docker Compose"

sh docker_install.sh
