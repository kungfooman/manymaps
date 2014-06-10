import os
import shutil
import subprocess
import struct
import binascii

def iwitodds(src, dst):
  cmd = "wine create_thumbnails/iwitodds.exe " + src + " " + dst
  print("CMD: " + cmd)
  os.system(cmd)

def ddstoiwi(src, dst):
  cmd = "wine create_thumbnails/dds2iwi.exe " + src
  print("CMD: " + cmd)
  os.system(cmd)
  outname = src.replace(".dds", ".iwi")
  if not os.path.exists(os.path.dirname(dst)):
    os.makedirs(os.path.dirname(dst))
  shutil.copy(outname, dst)

def dxtscale(src, dst, scale):
  cmd = "wine create_thumbnails/nvdxt.exe -dxt5 -nomipmap -rel_scale " + str(scale) + " " + str(scale) + " -RescaleBox -overwrite -file \"" + src + "\" -outfile " + dst
  print("CMD: " + cmd)
  os.system(cmd)
  
def createThumbnailOfMaterial(mapname, source):
  #source = "."
  startdir = "create_thumbnails"
  output = "thumbnails"
  dest_mapimages_mat = output + "/materials"
  dest_mapimages_img = output + "/images"
  
  
  
  # get original .iwi, convert it to dds, resize, convert back, store as thumbnail_[mapname], create material

  tmp = os.path.join(startdir, "tmp");
  if os.path.exists(tmp):
    shutil.rmtree(tmp)
  os.makedirs(tmp)

  #imgname = "thumbnail_" + mapname
  # turn thumbnail_mp_carentan into T_carentan
  imgname = "t_" + mapname[3:] # lower gamestate caused by precacheMaterial()
 
  iwitodds(
    os.path.join(source, "images", "loadscreen_" + mapname + ".iwi"),
    os.path.join(tmp, imgname + ".dds")
  )

  fs = os.path.getsize(os.path.join(tmp, imgname + ".dds"))
  if fs/1024 < 16:
      print("bad image, dont rescale, use white instead ok?")
      return
      
  if(fs/1024 < 256):
      scale = 1
  elif(fs/1024 < 1024):
      scale = 0.5
  elif(fs/1024 < 4096):
      scale = 0.25
  else:
      scale = 0.125
  if(scale != 1):
      file = tmp + "/" + imgname + ".dds"
      dxtscale(file, file, scale)
      
  fs = os.path.getsize(os.path.join(tmp, imgname + ".dds"))
  if(fs/1024 < 256):
      scale = 1
  elif(fs/1024 < 1024):
      scale = 0.5
  elif(fs/1024 < 4096):
      scale = 0.25
  else:
      scale = 0.125
  if(scale != 1):
      file = tmp + "/" + imgname + ".dds"
      dxtscale(file, file, scale)
  
  ddstoiwi(
    os.path.join(tmp, imgname + ".dds"),
    os.path.join(dest_mapimages_img, imgname + ".iwi")
  )
  
  #now create the material file...
  filename_material = os.path.join(dest_mapimages_mat, imgname)
  if not os.path.exists(os.path.dirname(filename_material)):
    os.makedirs(os.path.dirname(filename_material))
  mat_dest = open(filename_material, "wb")
  mat_src = open(os.path.join(startdir, "loadingscreen_material_template_1"),"rb")
  data1 = mat_src.read()
  mat_src.close()
  mat_src = open(os.path.join(startdir, "loadingscreen_material_template_2"),"rb")
  data2 = mat_src.read()
  mat_src.close()
  mat_src = open(os.path.join(startdir, "loadingscreen_material_template_3"),"rb")
  data3 = mat_src.read()
  mat_src.close()
  mat_src = open(os.path.join(startdir, "loadingscreen_material_template_4"),"rb")
  data4 = mat_src.read()
  mat_src.close()
  a = 84 + len(imgname)
  b = 85 + 2 * len(imgname)
  
  aa = struct.pack('B',a)
  bb = struct.pack('B',b)
  mat_dest.write(data1)
  mat_dest.write(aa)
  mat_dest.write(data2)
  mat_dest.write(bb)
  mat_dest.write(data3)
  mat_dest.write(aa)
  mat_dest.write(data4)
  mat_dest.close()
  mat_dest = open(os.path.join(dest_mapimages_mat, imgname), "a")
  mat_dest.write(imgname)
  mat_dest.close()
  mat_dest = open(os.path.join(dest_mapimages_mat, imgname), "ab")
  mat_dest.write(binascii.a2b_hex("00"))
  mat_dest.close()
  mat_dest = open(os.path.join(dest_mapimages_mat, imgname), "a")
  mat_dest.write(imgname)
  mat_dest.close()
  mat_dest = open(os.path.join(dest_mapimages_mat, imgname), "ab")
  mat_dest.write(binascii.a2b_hex("00"))
  mat_dest.close()
  mat_dest = open(os.path.join(dest_mapimages_mat, imgname), "a")
  mat_dest.write("colorMap")
  mat_dest.close()
  mat_dest = open(os.path.join(dest_mapimages_mat, imgname), "ab")
  mat_dest.write(binascii.a2b_hex("00"))
  mat_dest.close()
  mat_src.close()

  print(imgname)

# python2.6 -c 'from create_thumbnails import *; createThumbnailOfMaterial("mp_noko", "missing")'
  
#createThumbnailOfMaterial("mp_noko", "missing")