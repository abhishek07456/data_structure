/* Q: Write a program to accept N numbers from the user in one array and M numbers in another array. Then, sort the
      arrays using Selection Sort and then merge these two arrays using Merge Sort. */
#include<iostream>
using namespace std;
void swap(int* a, int* b)
{
    int t = *a;
    *a = *b;
    *b = t;
}
void selectionsort(int arr[],int n)
{
  for (int i = 0; i < n-1; i++)
    {
        int min = i;
        for (int j = i+1; j < n; j++)
          if (arr[j] < arr[min])
            min = j;
        swap(&arr[min], &arr[i]);
    }
}
void mergesort(int arr1[],int arr2[],int n1,int n2)
{
	int i=0,j=0,k=0;
	int res[n1+n2];
	while (i < n1 && j < n2)
    {
        if (arr1[i] <= arr2[j])
        {
            res[k] = arr1[i];
            i++;
        }
        else
        {
            res[k] = arr2[j];
            j++;
        }
        k++;
    }
    while (i < n1)
    {
        res[k] = arr1[i];
        i++;
        k++;
    }
    while (j < n2)
    {
        res[k] = arr2[j];
        j++;
        k++;
    }
    cout<<"Sorted Array : ";
    for(int i=0;i<k;i++)
    {
    	cout<<res[i]<<" ";
	}
}
int main()
{
	int n,m;
	cout<<"Enter the size of first array : ";
	cin>>n;
	int arr1[n];
	cout<<"Enter elements ->"<<endl;
	for(int i=0;i<n;i++)
      cin>>arr1[i];
    cout<<"Enter the size of second array : ";
    cin>>m;
    int arr2[m];
    cout<<"Enter elements ->"<<endl;
	for(int i=0;i<m;i++)
      cin>>arr2[i];
    selectionsort(arr1,n);
    selectionsort(arr2,m);
    mergesort(arr1,arr2,n,m);
    return 0;
}
