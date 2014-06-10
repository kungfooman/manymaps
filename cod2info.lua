
ffi = require "ffi"

ffi.cdef [[
typedef struct
{
	float position[3];
	float normal[3];
	int bla[11];
} aDrawVert;

typedef struct
{
	int length;
	int offset;
} aLump;

typedef struct
{
	char ident[4];
	int version;
	aLump lumps[100];
} aHeader;

typedef struct
{
	short index[3];
} aTriangle; // depricated

typedef struct
{
	unsigned short offset;
	//unsigned short b;
	//unsigned short c;
} aDrawIndex;

/*typedef struct
{
	unsigned short a;
	unsigned short b;
	unsigned short c;
} aDrawIndex;*/


// is empty in mp_analyse1
typedef struct
{
	float position[3];
} aCollisionVert;

typedef struct
{
	int planeIndex;
	int children[2];
	int mins[3];
	int maxs[3];
} aNode;

typedef struct
{
	//int cluster; // ???
	//int area; // ???
	//int mins[3]; // ???
	//int maxs[3]; // ???
	//int faceFirst;
	//int faceCount;
	//int brushFirst;
	//int brushCount;
	int someA[4];
	int firstLeafBrush; // lookup in leafbrushes
	int numLeafBrushes;
	int someB[3];
} aLeaf;

typedef struct
{
	unsigned short bla;
	unsigned short foo;
	unsigned int vertexFirst;
	unsigned short nVertex;

	unsigned short nTriangles;
	unsigned int triangleFirst;
} aTriangleSoup;

// fit Daevius' struct, nice for fast testing
typedef union
{
	unsigned char b[4];
	int i;
	unsigned int u;
	float f;
} DoubleWord;

typedef struct
{
	char name[64];
	DoubleWord a;
	DoubleWord b;
} aMaterial;

]]


function calcLump(lump_nr, ffi_type)
	offset = header.lumps[lump_nr].offset
	length = header.lumps[lump_nr].length
	--print("lump_nr: " .. lump_nr .. " ptr: " .. tonumber(ffi.cast("int", offset)))
	pointer = ffi.cast(ffi_type, buf+offset)
	n = length / ffi.sizeof(pointer[0])
	return pointer, n
end


function readAll(file)
    local f = io.open(file, "rb")
    local content = f:read("*all")
    f:close()
    return content
end

-- e.g. in PHP: luajit -l cod2info -e 'bsp("maps/mp/mp_run.d3dbsp");listMaterials()'
-- interactive: luajit -l cod2info -e 'bsp("maps/mp/mp_run.d3dbsp");listMaterials()' -i

-- bsp("maps/mp/mp_run.d3dbsp")
-- listMaterials()
function bsp(name)
	--file = readAll("Z:\\COD2MODDING\\mp_toujane.d3dbsp")
	file = readAll(name)
	buf  = ffi.new("unsigned char["..#file.."]")
	ffi.copy(buf, file, #file)
	header = ffi.cast("aHeader *", buf)
	
	-- brushes, nBrushes = calcLump(6, "aBrush *")
	materials, nMaterials = calcLump(0, "aMaterial *")
	triangleSoups, nTriangleSoups = calcLump(7, "aTriangleSoup *")
	vertices, nVertices = calcLump(8, "aDrawVert *")
	drawIndexes, nDrawIndexes = calcLump(9, "aDrawIndex *")

	--infoTriangles()
	--info()
end

function infoTriangles()
	for i = 0, 5 do
		print(i .. " triangles: " .. triangleSoups[i].nTriangles)
	end
end

function info()
	print("Size: " .. #file .. " Ident: " .. ffi.string(header[0].ident))
end

function listMaterials()
	for i=0,nMaterials-1 do
		print(ffi.string(materials[i].name))
	end
end

function bspdraw()

	colors = {
		{0, 0, 0},
		{0, 0, 1},
		{0, 1, 0},
		{0, 1, 1},
		{1, 0, 0},
		{1, 0, 1},
		{1, 1, 0},
		{1, 1, 1}
	}

	for i = 0,nTriangleSoups-1 do
		soup = triangleSoups + i
		
		gl.glBegin(gl.GL_TRIANGLES)
		for nTriangles = 0, soup.nTriangles-1 do
			drawIndex = drawIndexes + (soup.triangleFirst + nTriangles)
			
			co = colors[(nTriangles%8) + 1]
			gl.glColor3f(co[1], co[2], co[3])
			
			a  = vertices + (drawIndex.offset + soup.vertexFirst)
			gl.glVertex3fv(a.position)
		end
		gl.glEnd()
	end
end

ffi.cdef [[
	typedef struct {
		unsigned char *first;
	} fileMaterial;
]]
--getImagesOfMaterial("egypt_concrete_exteriorbunker1_top")
function getImagesOfMaterial(filename)
	content = readAll(filename)
	--print(content)
	mat_buf = ffi.new("unsigned char["..#content.."]")
	ffi.copy(mat_buf, content, #content)
	mat = ffi.cast("fileMaterial *", mat_buf)
	
	start = tonumber(ffi.cast("int", mat.first))
	
	--print("offset: " .. start)
	
	toIgnore = {
		["colorMap"]=1,
		["detailMap"]=1,
		["normalMap"]=1,
		["specularMap"]=1,
		["detailScale"]=1
	}
	
	ret = {}
	i = start
	while i<#content-1 do
	
		len = #ffi.string(mat_buf+i)
		if len > 0 then
			str = ffi.string(mat_buf+i)
			if not toIgnore[str] then
				-- print("1st: " .. str)
				table.insert(ret, str)
			end
		else
			len = 1		
		end
		i = i + len
	end

	i = 0
	for k,v in pairs(ret) do
		if (i ~= 0) then -- 1st ist the filename itself
			--print("images/" .. v .. ".iwi")
			print(v .. ".iwi")
		end
		i = i + 1
	end
end

