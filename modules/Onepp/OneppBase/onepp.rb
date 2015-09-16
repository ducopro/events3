*******************************************************************************
*** We use the .rb extention on the file because of the syntax highlighting ***
*******************************************************************************
-title=One Page Presence
-description=Bootstrap based marketing websites made easy ..
-theme=sandstone
-icon=fa-globe
-iconsize=48

#tables

    **************
    ** Reseller **
    **************        
    #sales
        -id=10
        -title=Sales Representative
        -description=Onepp sites are created by our representatives and never by the clients itself. Our reps can log in to the system and manage their projects themselves.
        -icon=fa-users
        -trail=%Name%
        #list
            -_edit
            -_site
            -Name
            -Id
            -Description
            -_delete
        #childs
            -site
        #fields
            #Name
                -title=Name
            #Id
                -title=Telephone
            #Description
                -title=Email
            #Adress
                -type=address
                -title=Address
    
    **********
    ** SITE **
    **********        
    #site
        -id=20
        -title=Website
        -description=A website consists of metadata and sections for showing information. Websites can be reached on a standard url, or custom domain names can be attached.
        -icon=fa-globe
        -trail=%Name%
        #list
            -_edit
            -_copy
            -_section
            -Icon
            -Name
            -Click=Subdomain
            -Theme
            -_delete
        #childs
            -section
        #fields
            #Click
                -title=%Id%
                -description=View Site
                -target=_blank
                -type=virtual
                -href=/oneppv/%Id%
                -icon=arrow-right
                -class=btn btn-xs btn-success
            #Name
                -title=Name Website
                -description=Mostly used as title of the website. see also: icon
                -cols=3
            #Char_2
                -title=Company Name
                -description=Will be used in the copyright area
                -cols=3
            #Id
                -title=Subdomain
                -description=Internal representation of this site. Must be unique.
                -cols=3
                #validate
                    -required=You need to fill in a unique identifier for this website  
            #Char_1
                -title=Custom Domain Name
                -description=A registered domain name can be pointed to the Onepp server. The site will also be served under this name.
                -cols=3
            #Theme
                -title=Layout
                -description=Make a selection from the existing base-layouts
                -type=select
                -cols=6
                #options
                    -creative=Creative
                    -mos=Mos7
                    -vitality=Vitality
                    -vitality_modern=Vitality (Modern look)
                    -vitality_vintage=Vitality (Vintage look)
            #Icon
                -title=Icon
                -icon=flag
                -value=column
                -description=An internal name from the Font Awesome library. Nu need to use the fa- prefix. use a name like car, calculator or coffee. This one is mostly rendered left next to the title of the website.
                -cols=3
                -template=<i style="color:%SupportColor%;" class="fa fa-%Icon%"></i>
            #SupportColor
                -title=Support Color
                -description=In some cases the theme will support an extra color to tweak the look and feel. Set it here. <strong>To reset:</strong> set it to black (#000000) 
                -type=color
                -template=<div style="background-color:%SupportColor%;width:1em;height:1em;" />
                -ajax=1
                -cols=3
            #Description
                -title=Client
                -type=textarea
                -rows=10
                -description=Information about this client. For internal use only. Full name and address.
                -cols=12
                
    *************
    ** SECTION **
    *************        
    #section
        -id=30
        -title=Section
        -description=Section are horizontal pieces of information on the website. The type of section defines what to show. For instance a slider, a google map, contact information or a set of columns with custom text.
        -icon=fa-paragraph
        -trail=%Char_1%-%Menu%
        #childs
            -column
        #sort
            -Weight=asc
        #list
            -Weight
            -_edit
            -_copy
            -_column
            -Icon
            -Menu=Menu
            -Char_1=Type
            -BG_color=Color
            -_delete
        #groups
            #content
               -title=Text
               -icon=fa-edit
               -description=Give your section a custom heading and an optional icon   
               -cols=6
            #general
                -title=Background
                -description=These items are used for every section.
                -icon=fa-home
                -cols=6
            #cta
                -title=Call To Action
                -description=Create a link to a different section or an external URL
                -icon=fa-link
        #fields
            #Weight
                -title=Order
                -group=content
                -cols=2
                -description=Sections are sorted by this number. The do not need to be unique and the serie can contain gaps.
            #Menu
                -title=Menu Name
                -description=If you want this section to appear in the menu, give it a name. Make it short and simple. If you leave this empty you can still go tom this section by scrolling. Note that the first section gets a link automaticly.
                -cols=5
                -group=content
            #Char_1
                -title=Section Type
                -description=What type of section should be showed? Fill in the corresponding section info, and optionally the underlying column information. Most of the time the first section needs to be shown as Home. Try this first.
                -type=select
                -cols=5
                -group=content
                -options=@@ThemeSections
            #BG_picture
                -title=Background Picture
                -type=file
                -description=Pictures you upload are served from the OnePP server. Be sure to make them as small as possible because they can impact performance. If you have the choide use the Background URL.
                -group=general

            #BG_color
                -title=Background Color
                -description=To reset the background to the theme default use the color black: #000000 Any other color will be applide to the section. Note that pictures take presedence.
                -type=color
                -group=general
                -template=<div style="background-color:%BG_color%;width:1em;height:1em;" />
                -cols=2
            #BG_height
                -title=Height
                -type=number
                -icon=plus
                -value=0
                -description=Set a fixed height, or set at zero (0) if you want a flexible height. Usefull for slider or fixed full width photo. 
                -group=general
                -cols=2
            #BG_column_width
                -title=Column Width (1-12)
                -description=The grid is divided in 12 even columns. Specify the default width of the column here. At the column level you can specify an offset and a different width. If you need an intelligent default value based on the number of content items/columns, just leave it empty or set it to zero.
                -value=0
                -group=general
                -type=number
                -cols=2
            #BG_Url
                -title=Background Picture URL
                -description=A background url has precedence over an uploaded picture. This is the best solution for performance because these pictures are swerved from different servers. Also ideal for scaffolding. Just search a picture at google and cut&past the yurl
                -group=general
            #Adres
                -title=Address
                -type=address
                -group=general
                -description=If you want the background of the section to show up as a Google-maps picture just specify the address here. If the address is set, it will overrule all the other picture settings. But only if the theme support it.
            #Icon
                -group=content
                -title=Icon
                -icon=flag
                -value=down
                -description=An internal name from the Font Awesome library. Nu need to use the fa- prefix. use a name like car, calculator or coffee
                -template=<i class="fa fa-%Icon%"></i>
                -cols=2
            #Id
                -title=Header
                -group=content
                -cols=5
            #Name
                -title=Subheader
                -group=content
                -cols=5
            #Text_1
                -title=Content
                -type=textarea
                -rich=1
                -group=content
                -rows=2
                -cols=12
            #Section
                -title=Internal Link
                -icon=link
                -type=select
                -group=cta
                -description=If you want a link to a different section of this website, select the corresponding section from the list.
                -cols=6
                #options
                    -table=section
                    -display=%Menu%  [%%Char_1%%]
                    -optional=1
            #Text_2
                -title=Titel
                -description=Optional text for the call to action
                -type=text
                -group=cta   
                -cols=6
            #Description
                -title=External Link
                -icon=link
                -type=url
                -group=cta
                -description=Url to an external website. The page will be openend in a different tab.
                
    *************
    ** CONTENT **
    *************        
    #column
        -id=40
        -title=Column
        -description=Sections can be divided into columns. Columns wrap around so they are showed next to each other on wide screens, but under eachother on small screens. Columns can contain an Icon, a customk picture, a heading, subheading and custom text.
        -icon=fa-columns
        #list
            -_edit
            -_copy
            -Weight
            -Icon
            -Id
            -Category
            -Int_1
            -Int_2
            -_delete
        #groups
            #content
                -cols=6
                -icon=fa-comments
                -title=Content
                -description=Textual Information
            #internal
                -icon=fa-lock
                -title=Advanced Settings
                -icon=wrench
            #pics
                -icon=fa-camera
                -title=Pictures
                -cols=6
            #smi
               -title=Social Media
               -description=Full url to the corresponing social media website & account
               -icon=fa-share
               -cols=6
        #fields
            #Weight
                -title=Order
                -cols=2
                -group=internal
            #Int_1
                -title=Column Offset
                -value=0
                -type=number
                -icon=plus
                -cols=2
                -group=internal
            #Int_2
                -title=Column Width
                -value=0
                -type=number
                -icon=plus
                -cols=2        
                -group=internal
            #Category
                -title=Category
                -description=This optional value can be used to divide the content in different categories. If it is supported by the theme you can use it for example in a portfolio or FAQ. Try it to see how it is rendered by the theme.
                -cols=6
                -group=internal
            #Section
                -title=Internal Link
                -icon=link
                -type=select
                -group=internal
                -description=If you want a link to a different section of this website, select the corresponding section from the list.
                -cols=2
                #options
                    -table=section
                    -display=%Menu%  [%%Char_1%%]
                    -optional=1
            #Description
                -title=External Link
                -icon=link
                -type=url
                -group=internal
                -description=Url to an external website. The page will be openend in a differt tab.
                -cols=4
            #Text_2
                -title=Link text
                -description=If the internal- or external link is used to render a call to action.
                -group=internal
                -cols=6    
            #Id
                -title=Header
                -group=content
            #Name
                -title=Subheader
                -group=content
            #Icon
                -group=pics
                -title=Icon
                -icon=flag
                -value=column
                -description=An internal name from the Font Awesome library. Nu need to use the fa- prefix. use a name like car, calculator or coffee
                -template=<i class="fa fa-%Icon%"></i>
            #Text_1
                -title=Content
                -type=textarea
                -rich=1
                -group=content
                -cols=12
            #BG_picture
                -title=Picture
                -type=file
                -description=Pictures you upload are served from the OnePP server. Be sure to make them as small as possible because they can impact performance. If you have the choide use the Background URL.
                -group=pics
            #BG_Url
                -title=Picture URL
                -description=A background url has precedence over an uploaded picture. This is the best solution for performance because these pictures are swerved from different servers. Also ideal for scaffolding. Just search a picture at google and cut&past the yurl
                -group=pics
            #smi_twitter
                -title=Twitter
                -icon=fa-twitter
                -group=smi
            #smi_facebook
                -title=Facebook
                -icon=fa-facebook
                -group=smi
            #smi_linkedin
                -title=Linked-In
                -icon=fa-linkedin
                -group=smi
            #smi_google-plus
                -title=Google Plus
                -icon=fa-google-plus
                -group=smi
            #smi_github
                -title=Github
                -icon=fa-github
                -group=smi
            #smi_pinterest
                -title=Pinterest
                -icon=fa-pinterest
                -group=smi
            #smi_tumblr
                -title=Tumblr
                -icon=fa-tumblr
                -group=smi
            #smi_instagram
                -title=Instagram
                -icon=fa-instagram
                -group=smi
            #smi_vk
                -title=V-Kontakt (VK)
                -icon=fa-vk
                -group=smi
            #smi_flickr
                -title=Flickr
                -icon=fa-flickr
                -group=smi
            #smi_pinterest
                -title=Pinterest
                -icon=fa-pinterest
                -group=smi 
                  