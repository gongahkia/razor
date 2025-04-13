from diagrams import Diagram, Cluster, Edge
from diagrams.programming.framework import Vue
from diagrams.programming.language import PHP
from diagrams.onprem.database import PostgreSQL
from diagrams.onprem.client import User
from diagrams.onprem.network import Nginx
from diagrams.generic.storage import Storage

graph_attr = {
    "fontsize": "20",
    "bgcolor": "white",
    "rankdir": "LR", 
    "splines": "ortho", 
    "nodesep": "0.8",
    "ranksep": "1.0",
    "fontname": "Sans-Serif",
    "fontcolor": "#2D3436",
    "pad": "0.4"
}
node_attr = {
    "fontsize": "12",
    "fontname": "Sans-Serif",
    "shape": "box",
    "style": "rounded",
    "labelloc": "b",  
    "imagepos": "tc",  
    "width": "1.6",  
    "height": "1.8",  
    "imagescale": "true",
    "fontcolor": "#2D3436",
    "margin": "0.2"    
}
edge_attr = {
    "fontsize": "11",
    "fontname": "Sans-Serif",
    "fontcolor": "#2D3436"
}
with Diagram(
    "Razor App Architecture", 
    show=False, 
    direction="LR",  
    graph_attr=graph_attr,
    node_attr=node_attr,
    edge_attr=edge_attr
):
    user = User("User")
    with Cluster("Frontend (Vue.js)"):
        vue = Vue("App.vue")
        login_form = Vue("LoginForm")
        password_list = Vue("PasswordList")
        password_form = Vue("PasswordForm")
        vue - Edge(color="#27ae60", style="solid", penwidth="2.0") - login_form
        vue - Edge(color="#27ae60", style="solid", penwidth="2.0") - password_list
        vue - Edge(color="#27ae60", style="solid", penwidth="2.0") - password_form
    with Cluster("Backend (PHP)"):
        nginx = Nginx("Web Server")
        php = PHP("index.php")
        auth = PHP("Authentication")
        pass_mgmt = PHP("Password\nManagement")
        nginx >> Edge(color="#27ae60", penwidth="2.0") >> php
        php >> Edge(color="#2980b9", penwidth="1.5") >> auth
        php >> Edge(color="#2980b9", penwidth="1.5") >> pass_mgmt
    with Cluster("Data Layer"):
        db = PostgreSQL("Password\nDatabase")
        encryption = Storage("Encryption\nModule")
        encryption - Edge(color="#8e44ad", style="dashed", penwidth="1.5") - db
    user >> Edge(color="#e67e22", penwidth="2.0") >> vue
    vue >> Edge(color="#27ae60", label="API Requests", penwidth="2.0") >> nginx
    auth >> Edge(color="#e74c3c", style="dotted", label="Verify", penwidth="1.5") >> db
    pass_mgmt >> Edge(color="#3498db", label="Store/Retrieve", penwidth="1.5") >> db