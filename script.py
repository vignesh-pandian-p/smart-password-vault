import os

folder_path = r"C:\xampp\htdocs\smart-password-vault"
output_file = "all_codes.txt"

text_extensions = ('.php', '.sql', '.txt', '.css', '.js', '.py')
skip_dirs = {'assets', '.git'}

with open(output_file, "w", encoding="utf-8") as out:
    # First write root files
    for filename in sorted(os.listdir(folder_path)):
        file_path = os.path.join(folder_path, filename)
        if os.path.isfile(file_path) and filename != output_file and filename.endswith(text_extensions):
            out.write(f"\n===== {filename} =====\n\n")
            with open(file_path, "r", encoding="utf-8", errors="ignore") as f:
                out.write(f.read())
                out.write("\n\n")

    # Then write includes
    includes_dir = os.path.join(folder_path, "includes")
    if os.path.isdir(includes_dir):
        for filename in sorted(os.listdir(includes_dir)):
            file_path = os.path.join(includes_dir, filename)
            if os.path.isfile(file_path) and filename.endswith(text_extensions):
                rel_name = os.path.join("includes", filename)
                out.write(f"\n===== {rel_name} =====\n\n")
                with open(file_path, "r", encoding="utf-8", errors="ignore") as f:
                    out.write(f.read())
                    out.write("\n\n")

print(f"✅ All code merged into {output_file}")
