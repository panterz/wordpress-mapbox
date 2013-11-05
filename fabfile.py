from fabric.api import env, put, run, task, prompt

@task
def deploy():
    """Defines devel environment"""
    user = prompt("Can you give me the username: ")
    env.user = user
    path = prompt("Can you give me the path of wordpress installation: ")
    env.account = "/home/%s" % env.user
    env.base_dir = "%(account)s/%(path)s" % {'account': env.account, 'path': path}
    app = "mapbox_mapper"
    run("if [ ! -d \"%(wdps_path)s/wp-content/plugins/%(app)s\"]; then mkdir %(wdps_path)s/wp-content/plugins/%(app)s; fi" % {'wdps_path':env.base_dir, 'app': app})
    put("%(dir)s" % { 'dir':app }, "%(wdps_path)s/wp-content/plugins" % { 'wdps_path':env.base_dir, 'app': app })


#def _create_db():
    #create database amarokdb;
    #grant usage on *.* to amarokuser@localhost identified by 'amarokpasswd';
    #grant all privileges on amarokdb.* to amarokuser@localhost ;