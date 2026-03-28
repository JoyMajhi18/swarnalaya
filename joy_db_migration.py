import os
import pymysql
import argparse
from dotenv import load_dotenv
import datetime
import re

# Load environment variables
load_dotenv()

DB_HOST = os.getenv("DB_HOST", "127.0.0.1")
DB_PORT = int(os.getenv("DB_PORT", 3306))
DB_USER = os.getenv("DB_USER", "root")
DB_PASS = os.getenv("DB_PASS", "")
DB_NAME = os.getenv("DB_NAME", "joy")
SQL_FILE = "joy_migration.sql"

def get_connection(include_db=True):
    """Establish a connection to the MySQL server."""
    try:
        return pymysql.connect(
            host=DB_HOST,
            port=DB_PORT,
            user=DB_USER,
            password=DB_PASS,
            database=DB_NAME if include_db else None,
            charset='utf8mb4',
            cursorclass=pymysql.cursors.DictCursor
        )
    except Exception as e:
        print(f"Error connecting to database: {e}")
        return None

def export_db():
    """Export the entire database schema and data to a SQL file."""
    print(f"Starting export of database '{DB_NAME}'...")
    conn = get_connection()
    if not conn:
        return

    try:
        with conn.cursor() as cursor:
            # Get all tables
            cursor.execute("SHOW TABLES")
            tables = [list(row.values())[0] for row in cursor.fetchall()]
            
            if not tables:
                print("No tables found in the database.")
                return

            with open(SQL_FILE, "w", encoding="utf-8") as f:
                f.write(f"-- Joy DB Migration Dump\n")
                f.write(f"-- Generated: {datetime.datetime.now()}\n\n")
                f.write(f"SET FOREIGN_KEY_CHECKS = 0;\n\n")

                for table in tables:
                    print(f"Exporting table: {table}")
                    
                    # Get table creation schema
                    cursor.execute(f"SHOW CREATE TABLE `{table}`")
                    create_stmt = cursor.fetchone()['Create Table']
                    f.write(f"DROP TABLE IF EXISTS `{table}`;\n")
                    f.write(f"{create_stmt};\n\n")

                    # Get table data
                    cursor.execute(f"SELECT * FROM `{table}`")
                    rows = cursor.fetchall()
                    
                    if rows:
                        f.write(f"-- Table data: {table}\n")
                        for row in rows:
                            # Safely format values
                            formatted_values = []
                            for val in row.values():
                                if val is None:
                                    formatted_values.append("NULL")
                                elif isinstance(val, (int, float)):
                                    formatted_values.append(str(val))
                                else:
                                    # Escape single quotes
                                    escaped_val = str(val).replace("'", "''")
                                    formatted_values.append(f"'{escaped_val}'")
                            
                            cols = ", ".join([f"`{c}`" for c in row.keys()])
                            vals = ", ".join(formatted_values)
                            f.write(f"INSERT INTO `{table}` ({cols}) VALUES ({vals});\n")
                        f.write("\n")

                f.write(f"SET FOREIGN_KEY_CHECKS = 1;\n")
                print(f"Successfully exported to {SQL_FILE}")

    finally:
        conn.close()

def restore_db():
    """Restore the database from the SQL file."""
    if not os.path.exists(SQL_FILE):
        print(f"Error: Migration file '{SQL_FILE}' not found.")
        return

    print(f"Starting restoration to database '{DB_NAME}'...")
    
    # Connect without specifying database first to create it if needed
    conn = get_connection(include_db=False)
    if not conn:
        return

    try:
        with conn.cursor() as cursor:
            # Create database
            cursor.execute(f"CREATE DATABASE IF NOT EXISTS `{DB_NAME}`")
            cursor.execute(f"USE `{DB_NAME}`")
            
            print(f"Reading {SQL_FILE}...")
            # We read the file and split by semicolon, but need to be careful with semicolons inside strings.
            # A more robust way is to execute statements one by one or use multi-statement mode.
            # To handle multiple statements, we'll reconnect with multi_statements=True
            conn.close()
            
            conn = pymysql.connect(
                host=DB_HOST,
                port=DB_PORT,
                user=DB_USER,
                password=DB_PASS,
                database=DB_NAME,
                charset='utf8mb4',
                client_flag=pymysql.constants.CLIENT.MULTI_STATEMENTS
            )
            
            with open(SQL_FILE, "r", encoding="utf-8") as f:
                sql_script = f.read()
                
            with conn.cursor() as cursor:
                # Use multi-statement execution
                cursor.execute(sql_script)
                print("All schema and data have been successfully seeded.")

    except Exception as e:
        print(f"Restore failed: {e}")
    finally:
        conn.close()

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Joy DB Migration Utility")
    parser.add_argument("--export", action="store_true", help="Dump database schema and data to SQL file")
    parser.add_argument("--restore", action="store_true", help="Restore database schema and data from SQL file")

    args = parser.parse_args()

    if args.export:
        export_db()
    elif args.restore:
        restore_db()
    else:
        parser.print_help()
