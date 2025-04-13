from diagrams import Diagram, Cluster, Edge
from diagrams.programming.framework import Vue
from diagrams.programming.language import NodeJS
from diagrams.firebase.develop import Authentication, RealtimeDatabase
from diagrams.onprem.client import User
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
    "Razor Password Manager Architecture", 
    show=False, 
    direction="LR",  
    graph_attr=graph_attr,
    node_attr=node_attr,
    edge_attr=edge_attr
):
    user = User("User")
    with Cluster("Frontend (Vue.js)"):
        vue = Vue("App.vue")
        login_view = Vue("LoginView")
        dashboard_view = Vue("DashboardView")
        setup_master_key = Vue("SetupMasterKey")
        vue - Edge(color="#27ae60", style="solid", penwidth="2.0") - login_view
        vue - Edge(color="#27ae60", style="solid", penwidth="2.0") - dashboard_view
        vue - Edge(color="#27ae60", style="solid", penwidth="2.0") - setup_master_key
    with Cluster("Backend (Node.js)"):
        express = NodeJS("Express Server")
        auth_service = NodeJS("Auth Service")
        password_service = NodeJS("Password Service")
        master_key_service = NodeJS("MasterKey Service")
        express >> Edge(color="#27ae60", penwidth="2.0") >> auth_service
        express >> Edge(color="#2980b9", penwidth="1.5") >> password_service
        express >> Edge(color="#2980b9", penwidth="1.5") >> master_key_service
    with Cluster("Data Layer (Firebase)"):
        firebase_auth = Authentication("Firebase\nAuthentication")
        firebase_db = RealtimeDatabase("Firebase\nRealtime DB")
        encryption = Storage("Client-side\nEncryption")
        encryption - Edge(color="#8e44ad", style="dashed", penwidth="1.5") - firebase_db
    user >> Edge(color="#e67e22", penwidth="2.0") >> vue
    vue >> Edge(color="#27ae60", label="API Requests", penwidth="2.0") >> express
    auth_service >> Edge(color="#e74c3c", style="dotted", label="Verify", penwidth="1.5") >> firebase_auth
    password_service >> Edge(color="#3498db", label="Store/Retrieve", penwidth="1.5") >> firebase_db
    master_key_service - Edge(color="#8e44ad", style="dashed", penwidth="1.5") - encryption