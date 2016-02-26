#! /usr/bin/env python
import sys
import os
from osgeo import osr

def shpToPgSQL(shapeprj_path, shp_path, sql_path, table_name, append):
   prj_file = open(shapeprj_path, 'r')
   prj_txt = prj_file.read()
   srs = osr.SpatialReference()
   srs.ImportFromESRI([prj_txt])
   srs.AutoIdentifyEPSG()
   prj = srs.GetAuthorityCode(None)
   if append == "True":
      os.system('shp2pgsql  -S -D -s ' + prj + ' -a -g geometries ' + shp_path + ' public.' + table_name + ' > ' + sql_path + '/file.sql')
   else:
      os.system('shp2pgsql  -S -D -s ' + prj + ' -g geometries ' + shp_path + ' public.' + table_name + ' > ' + sql_path + '/file.sql')
   sys.stdout.write(prj)
   sys.stdout.flush()
shpToPgSQL(sys.argv[1], sys.argv[2], sys.argv[3], sys.argv[4], sys.argv[5])


