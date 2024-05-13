import pandas as pd 
import numpy as np
import cv2
import os, glob, math
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.feature_extraction.text import CountVectorizer
from sklearn.metrics.pairwise import linear_kernel
from sklearn.metrics.pairwise import cosine_similarity
from sklearn.metrics.pairwise import euclidean_distances
from ast import literal_eval
from flask import Flask, request, jsonify
from flask_uploads import UploadSet, configure_uploads, IMAGES

app = Flask(__name__)

# Configure photo upload settings
photos = UploadSet('photos', IMAGES)
app.config['UPLOADED_PHOTOS_DEST'] = 'uploads'  # Save uploaded photos to the 'uploads' folder
configure_uploads(app, photos)

# load data
df1=pd.read_csv('tmdb_5000_credits.csv')
df2=pd.read_csv('tmdb_5000_movies.csv')
df1.columns = ['id','tittle','cast','crew']
df2= df2.merge(df1,on='id')

# top movies calculation
C= df2['vote_average'].mean()
m= df2['vote_count'].quantile(0.9)
q_movies = df2.copy().loc[df2['vote_count'] >= m]
def weighted_rating(x, m=m, C=C):
    v = x['vote_count']
    R = x['vote_average']
    # Calculation based on the IMDB formula
    return (v/(v+m) * R) + (m/(m+v) * C)
q_movies['score'] = q_movies.apply(weighted_rating, axis=1)
q_movies = q_movies.sort_values('score', ascending=False)

# similar movies calculation
tfidf = TfidfVectorizer(stop_words='english')
df2['overview'] = df2['overview'].fillna('')
tfidf_matrix = tfidf.fit_transform(df2['overview'])
cosine_sim = linear_kernel(tfidf_matrix, tfidf_matrix)
indices = pd.Series(df2.index, index=df2['title']).drop_duplicates()

# better similar
features = ['cast', 'crew', 'keywords', 'genres']
for feature in features:
    df2[feature] = df2[feature].apply(literal_eval)
def get_director(x):
    for i in x:
        if i['job'] == 'Director':
            return i['name']
    return np.nan
def get_list(x):
    if isinstance(x, list):
        names = [i['name'] for i in x]
        if len(names) > 3:
            names = names[:3]
        return names
    return []
df2['director'] = df2['crew'].apply(get_director)
features = ['cast', 'keywords', 'genres']
for feature in features:
    df2[feature] = df2[feature].apply(get_list)
def clean_data(x):
    if isinstance(x, list):
        return [str.lower(i.replace(" ", "")) for i in x]
    else:
        if isinstance(x, str):
            return str.lower(x.replace(" ", ""))
        else:
            return ''
features = ['cast', 'keywords', 'director', 'genres']
for feature in features:
    df2[feature] = df2[feature].apply(clean_data)
def create_soup(x):
    return ' '.join(x['keywords']) + ' ' + ' '.join(x['cast']) + ' ' + x['director'] + ' ' + ' '.join(x['genres'])
df2['soup'] = df2.apply(create_soup, axis=1)
count = CountVectorizer(stop_words='english')
count_matrix = count.fit_transform(df2['soup'])
cosine_sim2 = cosine_similarity(count_matrix, count_matrix)
df2 = df2.reset_index()
indices2 = pd.Series(df2.index, index=df2['title'])

# content based image retrieval
dataset_dir = "poster"
descriptors_dir = "feature"

if not os.path.exists(descriptors_dir):
    os.makedirs(descriptors_dir)

def get_descriptor(image_file):
    kernel        = cv2.getGaborKernel((21, 21), 8.0, np.pi/4, 10.0, 0.5, 0, ktype=cv2.CV_32F)
    kernel       /= math.sqrt((kernel * kernel).sum())
    ori_img       = cv2.imread(image_file)
    image         = cv2.cvtColor(ori_img, cv2.COLOR_BGR2GRAY)
    image         = cv2.resize(image, (500, 750), interpolation = cv2.INTER_LINEAR)
    filtered_img  = cv2.filter2D(image, cv2.CV_8UC3, kernel)
    heigth, width = kernel.shape
    
    # convert matrix to vector 
    texturedesc = cv2.resize(filtered_img, (3*width, 3*heigth), interpolation=cv2.INTER_CUBIC) / 255

    hsv = cv2.cvtColor(ori_img,cv2.COLOR_BGR2HSV)
    hue = cv2.calcHist([hsv], [0], None, [180], [0, 180])
    sat = cv2.calcHist([hsv], [1], None, [256], [0, 256])
    colordesc = np.hstack(np.concatenate((hue, sat), axis = None))
    colordesc /= colordesc.max()
    colordesc *= 10

    descriptor = np.hstack(np.concatenate((texturedesc, colordesc), axis = None))
    return descriptor


# Define Flask endpoint to receive movie recommendations
@app.route('/recommend', methods=['POST'])
def recommend_movies():
    global q_movies 
    ids = q_movies['id'].head(20).tolist()
    print(ids)
    return jsonify(ids)

# Define Flask endpoint to receive similar movie recommendations
@app.route('/similar', methods=['POST'])
def similar_movies():
    global indices, cosine_sim

    data = request.get_json()
    title = data.get('title')
    idx = indices[title]
    sim_scores = list(enumerate(cosine_sim[idx]))
    sim_scores = sorted(sim_scores, key=lambda x: x[1], reverse=True)
    sim_scores = sim_scores[1:6]
    movie_indices = [i[0] for i in sim_scores]
    ids = df2['id'].iloc[movie_indices]
    print(ids.tolist())
    return jsonify(ids.tolist())

# Define Flask endpoint to receive similar movie recommendations
@app.route('/similar2', methods=['POST'])
def similar_movies2():
    global indices2, cosine_sim2

    data = request.get_json()
    title = data.get('title')
    idx = indices2[title]
    sim_scores = list(enumerate(cosine_sim2[idx]))
    sim_scores = sorted(sim_scores, key=lambda x: x[1], reverse=True)
    sim_scores = sim_scores[1:6]
    movie_indices = [i[0] for i in sim_scores]
    ids = df2['id'].iloc[movie_indices]
    print(ids.tolist())
    return jsonify(ids.tolist())

# Define Flask endpoint to receive similar movie poster using cbir
@app.route('/cbir', methods=['POST'])
def cbir():
    data = request.get_json()
    poster = data.get('poster')
    test_descriptor = get_descriptor("poster"+poster)
    best_fit_images = []

    for descriptorfile in glob.glob(os.path.join(descriptors_dir,"*.npy")):
        descriptor = np.load(descriptorfile)
        distance = abs(euclidean_distances(descriptor.reshape(1, -1), test_descriptor.reshape(1, -1)))
        entry = {"image_path": descriptorfile.replace(".npy","").replace(descriptors_dir,""),
                "distance"  : distance
                }
        best_fit_images.append(entry)
        best_fit_images.sort(key= lambda x:x["distance"], reverse=False)
        best_fit_images = best_fit_images[:10]
    # print(best_fit_images)
    fit_file = [row["image_path"] for row in best_fit_images]
    print(fit_file)
    return jsonify(fit_file[1:6])

# Endpoint for uploading photos
@app.route('/upload', methods=['POST'])
def upload_photo():
    if 'photo' in request.files:
        filename = photos.save(request.files['photo'])
        print(filename)
        test_descriptor = get_descriptor("uploads/"+filename)
        best_fit_images = []

        for descriptorfile in glob.glob(os.path.join(descriptors_dir,"*.npy")):
            descriptor = np.load(descriptorfile)
            distance = abs(euclidean_distances(descriptor.reshape(1, -1), test_descriptor.reshape(1, -1)))
            entry = {"image_path": descriptorfile.replace(".npy","").replace(descriptors_dir,""),
                    "distance"  : distance
                    }
            best_fit_images.append(entry)
            best_fit_images.sort(key= lambda x:x["distance"], reverse=False)
            best_fit_images = best_fit_images[:10]
        # print(best_fit_images)
        fit_file = [row["image_path"] for row in best_fit_images]
        print(fit_file)
        return jsonify(fit_file)
    else:
        return jsonify("")

if __name__ == '__main__':
    app.run(debug=True, host="0.0.0.0")
