from database import engine
from sqlalchemy import text

def update_table():
    try:
        with engine.connect() as conn:
            # Check if category column exists, add if not
            conn.execute(text("ALTER TABLE products ADD COLUMN category VARCHAR(100) AFTER stock"))
            conn.commit()
            print("Successfully added 'category' column to 'products' table!")
    except Exception as e:
        if "Duplicate column name" in str(e):
            print("'category' column already exists.")
        else:
            print(f"Error updating table: {e}")

if __name__ == "__main__":
    update_table()
