import os
import re

reports = {
    'Sales': ('sales', 'dailySales', True),
    'Products': ('products', 'products', False),
    'Stocks': ('stocks', 'stocks', False),
    'Cashiers': ('cashiers', 'shifts', False),
    'Promotions': ('promotions', 'promotions', False),
    'Customers': ('customers', 'customers', False)
}

for folder, (prefix, propName, hasSecondTable) in reports.items():
    file_path = f"resources/js/Pages/Reports/{folder}/Index.vue"
    if not os.path.exists(file_path):
        continue
        
    with open(file_path, 'r') as f:
        content = f.read()
        
    # 1. Change Prop type from Array to Object
    content = content.replace(f"{propName}: Array", f"{propName}: Object")
    
    # 2. Change v-for and v-if
    content = content.replace(f"in {propName}", f"in {propName}.data")
    content = content.replace(f"{propName}.length", f"{propName}.data.length")
    
    # 3. Insert Pagination under the main table (but inside the card)
    # The card usually ends with </table>\n                </div>\n            </div>
    # We will find the first </table>\n                </div>
    table_end = "</table>\n                </div>"
    pagination_html = f"</table>\n                </div>\n                <Pagination class=\"mt-4\" :links=\"{propName}.links\" />"
    
    if hasSecondTable:
        # replace only the first occurrence for Sales (dailySales)
        content = content.replace(table_end, pagination_html, 1)
    else:
        # there is only one table
        content = content.replace(table_end, pagination_html)
        
    # 4. Import Pagination
    if "import Pagination" not in content:
        content = content.replace("import MainPageHeader", "import Pagination from '@/Components/Tables/Pagination.vue';\nimport MainPageHeader")
        
    # 5. Fix PDF Export Button
    pdf_btn_old = f"<a :href=\"route('reports.{prefix}.export.pdf', formFilters)\" target=\"_blank\" class=\"btn btn-outline-primary sm\">"
    pdf_btn_new = "<button @click=\"exportPdf\" class=\"btn btn-outline-primary sm\">"
    content = content.replace(pdf_btn_old, pdf_btn_new)
    
    content = content.replace("</button>\n                        </a>", "</button>\n                        </button>")
    content = content.replace("Ekspor PDF\n                        </a>", "Ekspor PDF\n                        </button>")
    
    # 6. Add exportPdf function
    if "const exportPdf" not in content:
        export_func = f"""
const exportPdf = () => {{
    router.post(route('reports.{prefix}.export.pdf'), formFilters.data(), {{
        preserveScroll: true,
        preserveState: true,
    }});
}};
"""
        content = content.replace("const exportCsv =", export_func + "\nconst exportCsv =")
        
    with open(file_path, 'w') as f:
        f.write(content)
        
    print(f"Patched {folder}/Index.vue")
