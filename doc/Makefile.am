Article_TEXS = gan_manual.tex

Article_PDF  = gan_manual.pdf

EXTRA_DIST=$(Article_TEXS)

noinst_DATA=$(Article_PDF)


$(Article_PDF) : $(Article_TEXS) 
	$(PDFLATEX) gan_manual
	$(PDFLATEX) gan_manual
	$(PDFLATEX) gan_manual

CLEANFILES = *.log *.aux *.toc *.bbl *.blg

DWSPDFS.zip : $(pkgdata_DATA)
	zip DWSPDFS.zip $(pkgdata_DATA)

